<?php

namespace App\Services\Inventory;

use App\Enums\PaymentType;
use App\Enums\TaxType;
use App\Models\PartyTransaction;
use App\Models\PurchaseInvoice;
use App\Models\StockTransaction;
use App\Models\SupplierPurchaseOrder;
use App\Models\SupplierPurchaseOrderItem;
use App\Services\CompanyTaxSetting;
use App\Services\Documents\DocumentDeletionGuard;
use App\Services\DocumentTaxCalculator;
use App\Services\PartyTransactionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly PartyTransactionService $partyTransactionService,
    ) {}

    /**
     * @return array<int, string>
     */
    public function purchaseOrderOptions(
        ?int $supplierId,
        ?int $currentPurchaseOrderId = null,
    ): array {
        if (
            ! $supplierId
            || ! Schema::hasTable('supplier_purchase_orders')
            || ! Schema::hasTable('supplier_purchase_order_items')
        ) {
            return [];
        }

        return SupplierPurchaseOrder::query()
            ->with('purchaseRequest:id,code')
            ->where('supplier_id', $supplierId)
            ->where(function (Builder $query) use ($currentPurchaseOrderId): void {
                $query
                    ->whereHas('items', fn (Builder $query): Builder => $query
                        ->whereRaw('supplier_purchase_order_items.ordered_quantity > (
                            SELECT COALESCE(SUM(purchase_invoice_items.quantity), 0)
                            FROM purchase_invoice_items
                            WHERE purchase_invoice_items.supplier_purchase_order_item_id = supplier_purchase_order_items.id
                        )'))
                    ->when(
                        $currentPurchaseOrderId,
                        fn (Builder $query): Builder => $query->orWhere(
                            'supplier_purchase_orders.id',
                            $currentPurchaseOrderId,
                        ),
                    );
            })
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(fn (SupplierPurchaseOrder $order): array => [
                $order->getKey() => $this->purchaseOrderOptionLabel($order),
            ])
            ->all();
    }

    public function purchaseOrderOptionLabel(SupplierPurchaseOrder $order): string
    {
        $parts = [
            $order->code,
            $order->purchaseRequest?->code,
            $order->order_date->format('d/m/Y'),
        ];

        if (filled($order->supplier_reference)) {
            $parts[] = $order->supplier_reference;
        }

        $parts[] = 'المتبقي: '.number_format($order->remainingToInvoiceQuantity(), 2);
        $parts[] = 'الإجمالي: '.number_format((float) $order->total, 2).' ج.م';

        return implode(' — ', array_filter($parts));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function importRemainingOrderItems(
        int $purchaseOrderId,
        ?PurchaseInvoice $excludingInvoice = null,
    ): array {
        return SupplierPurchaseOrderItem::query()
            ->where('supplier_purchase_order_id', $purchaseOrderId)
            ->with(['item:id,code,name', 'unit:id,name'])
            ->orderBy('sort_order')
            ->get()
            ->map(function (SupplierPurchaseOrderItem $orderItem) use ($excludingInvoice): ?array {
                $previouslyInvoiced = $orderItem->previouslyInvoicedQuantityBefore($excludingInvoice);
                $remaining = $orderItem->remainingToInvoiceQuantity($excludingInvoice);

                if ($remaining <= 0) {
                    return null;
                }

                return [
                    'supplier_purchase_order_item_id' => $orderItem->getKey(),
                    'item_id' => $orderItem->item_id,
                    'item_code' => $orderItem->item->code,
                    'item_name' => $orderItem->item->name,
                    'unit_id' => $orderItem->unit_id,
                    'unit_name' => $orderItem->unit->name,
                    'ordered_quantity' => (float) $orderItem->ordered_quantity,
                    'previously_invoiced_quantity' => $previouslyInvoiced,
                    'remaining_quantity' => $remaining,
                    'quantity' => $remaining,
                    'unit_cost' => (float) $orderItem->unit_price,
                    'tax_exempt' => false,
                    'tax_amount' => 0,
                    'total_cost' => round($remaining * (float) $orderItem->unit_price, 2),
                    'notes' => $orderItem->notes,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function purchaseOrderSelectionPayload(
        int $purchaseOrderId,
        ?PurchaseInvoice $excludingInvoice = null,
    ): array {
        $order = SupplierPurchaseOrder::query()->findOrFail($purchaseOrderId);

        return [
            'supplier_purchase_order_id' => $order->getKey(),
            'supplier_id' => $order->supplier_id,
            'warehouse_id' => $order->warehouse_id,
            'payment_type' => $order->payment_type?->value ?? PaymentType::Cash->value,
            'due_date' => $order->due_date?->format('Y-m-d'),
            'tax_type' => $order->tax_type?->value ?? TaxType::None->value,
            'items' => $this->importRemainingOrderItems($order->getKey(), $excludingInvoice),
        ];
    }

    public function create(array $data): PurchaseInvoice
    {
        $data['tax_type'] ??= app(CompanyTaxSetting::class)->resolve()->value;

        return DB::transaction(function () use ($data): PurchaseInvoice {
            $data['supplier_document_type'] = in_array(
                $data['supplier_document_type'] ?? 'invoice',
                ['price_statement', 'invoice'],
                true
            ) ? ($data['supplier_document_type'] ?? 'invoice') : 'invoice';

            $data['invoice_number'] = $data['supplier_document_type'] === 'invoice'
                ? $this->normalizeInvoiceNumber($data['invoice_number'] ?? null)
                : null;

            if ($data['supplier_document_type'] === 'invoice' && blank($data['invoice_number'])) {
                throw ValidationException::withMessages([
                    'invoice_number' => 'رقم فاتورة المورد مطلوب عند اختيار نوع المستند: فاتورة.',
                ]);
            }

            $this->validateSupplierInvoiceNumber(
                (int) ($data['supplier_id'] ?? 0),
                $data['invoice_number'],
            );

            $items = $this->validateAndNormalizeItems($data);
            $this->applyTaxTotals($data, $items);
            unset($data['items'], $data['code']);

            $invoice = PurchaseInvoice::create($data);
            $invoice->items()->createMany($items);
            $this->post($invoice);

            return $invoice->fresh(['items', 'supplier', 'warehouse', 'supplierPurchaseOrder']);
        });
    }

    public function update(PurchaseInvoice $invoice, array $data): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $data): PurchaseInvoice {
            $invoice = PurchaseInvoice::query()->lockForUpdate()->findOrFail($invoice->getKey());

            $data['supplier_document_type'] = in_array(
                $data['supplier_document_type'] ?? $invoice->supplier_document_type ?? 'invoice',
                ['price_statement', 'invoice'],
                true
            ) ? ($data['supplier_document_type'] ?? $invoice->supplier_document_type ?? 'invoice') : 'invoice';

            $data['invoice_number'] = $data['supplier_document_type'] === 'invoice'
                ? $this->normalizeInvoiceNumber($data['invoice_number'] ?? $invoice->invoice_number)
                : null;

            if ($data['supplier_document_type'] === 'invoice' && blank($data['invoice_number'])) {
                throw ValidationException::withMessages([
                    'invoice_number' => 'رقم فاتورة المورد مطلوب عند اختيار نوع المستند: فاتورة.',
                ]);
            }

            $this->validateSupplierInvoiceNumber(
                (int) ($data['supplier_id'] ?? $invoice->supplier_id),
                $data['invoice_number'],
                (int) $invoice->getKey(),
            );

            $data['tax_type'] ??= $invoice->tax_type?->value ?? TaxType::Vat14->value;
            $items = $this->validateAndNormalizeItems($data, $invoice);
            $this->applyTaxTotals($data, $items);
            unset($data['items'], $data['code'], $data['posted']);

            $invoice->update($data);
            $invoice->items()->delete();
            $invoice->items()->createMany($items);
            $this->post($invoice);

            return $invoice->fresh(['items', 'supplier', 'warehouse', 'supplierPurchaseOrder']);
        });
    }

    public function delete(PurchaseInvoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice): bool {
            $invoice = PurchaseInvoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            app(DocumentDeletionGuard::class)->assertCanDelete($invoice);
            $this->inventoryService->deleteDocumentTransactions($invoice->code);
            $this->partyTransactionService->deleteDocumentTransaction($invoice);
            app(\App\Services\JournalEntryService::class)->deleteForSource($invoice);
            $invoice->items()->delete();

            return (bool) $invoice->delete();
        });
    }

    public function post(PurchaseInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $invoice->load('items', 'supplier');

            $this->inventoryService->replaceVoucherTransactions(
                $invoice->code,
                $invoice->warehouse_id,
                $invoice->invoice_date,
                $invoice->notes,
                StockTransaction::TYPE_PURCHASE,
                $invoice->items,
            );

            $invoiceTotal = $invoice->totalAmount();
            $paymentType = $invoice->payment_type instanceof PaymentType
                ? $invoice->payment_type->value
                : (string) $invoice->payment_type;

            $this->partyTransactionService->replaceDocumentTransaction(
                $invoice->supplier,
                PartyTransaction::TYPE_PURCHASE_INVOICE,
                $invoice,
                $invoice->invoice_date,
                $paymentType === PaymentType::Cash->value ? $invoiceTotal : 0,
                $invoiceTotal,
                $invoice->code,
                $paymentType === PaymentType::Cash->value
                    ? ($invoice->notes ?: 'فاتورة شراء نقدية مسددة فورًا')
                    : $invoice->notes,
            );

            app(\App\Services\JournalEntryService::class)->postPurchaseInvoice($invoice);

            if (! $invoice->posted) {
                $invoice->posted = true;
                $invoice->save();
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateAndNormalizeItems(
        array $data,
        ?PurchaseInvoice $invoice = null,
    ): array {
        $supplierId = (int) ($data['supplier_id'] ?? 0);
        $purchaseOrderId = (int) ($data['supplier_purchase_order_id'] ?? 0);
        $items = $data['items'] ?? [];

        if ($supplierId <= 0) {
            throw ValidationException::withMessages(['supplier_id' => 'يجب اختيار المورد.']);
        }

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => $purchaseOrderId > 0
                    ? 'لا توجد كميات متبقية للفوترة في أمر التوريد المحدد.'
                    : 'يجب إضافة صنف واحد على الأقل.',
            ]);
        }

        $order = null;

        if ($purchaseOrderId > 0) {
            $order = SupplierPurchaseOrder::query()->find($purchaseOrderId);

            if (! $order || (int) $order->supplier_id !== $supplierId) {
                throw ValidationException::withMessages([
                    'supplier_purchase_order_id' => 'أمر التوريد المحدد لا يخص المورد المختار.',
                ]);
            }
        }

        $normalized = [];
        $seenOrderItems = [];

        foreach ($items as $index => $item) {
            $itemId = (int) ($item['item_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);

            if ($itemId <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.item_id" => 'يجب اختيار الصنف.',
                ]);
            }

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'يجب أن تكون كمية الفاتورة أكبر من صفر.',
                ]);
            }

            if ($unitCost < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_cost" => 'لا يمكن أن يكون سعر الوحدة سالبًا.',
                ]);
            }

            $orderItemId = (int) ($item['supplier_purchase_order_item_id'] ?? 0);

            if ($order) {
                if (isset($seenOrderItems[$orderItemId])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.item_id" => 'لا يمكن تكرار صنف أمر التوريد في الفاتورة.',
                    ]);
                }

                $orderItem = SupplierPurchaseOrderItem::query()
                    ->whereKey($orderItemId)
                    ->where('supplier_purchase_order_id', $order->getKey())
                    ->first();

                if (! $orderItem || (int) $orderItem->item_id !== $itemId) {
                    throw ValidationException::withMessages([
                        "items.{$index}.item_id" => 'الصنف لا ينتمي إلى أمر التوريد المحدد.',
                    ]);
                }

                if ($quantity > $orderItem->remainingToInvoiceQuantity($invoice) + 0.00001) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => 'كمية الفاتورة أكبر من الكمية المتبقية في أمر التوريد.',
                    ]);
                }

                $seenOrderItems[$orderItemId] = true;
            } elseif ($orderItemId > 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.item_id" => 'لا يمكن إضافة صنف مرتبط بأمر توريد دون اختيار أمر التوريد.',
                ]);
            }

            $normalized[] = [
                'supplier_purchase_order_item_id' => $order ? $orderItemId : null,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'tax_exempt' => (bool) ($item['tax_exempt'] ?? false),
                'tax_amount' => 0,
                'total_cost' => round($quantity * $unitCost, 2),
                'notes' => $item['notes'] ?? null,
            ];
        }

        return $normalized;
    }

    private function normalizeInvoiceNumber(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return ($value === '' || $value === '0') ? null : $value;
    }

    private function validateSupplierInvoiceNumber(
        int $supplierId,
        ?string $invoiceNumber,
        ?int $excludingInvoiceId = null,
    ): void {
        if ($supplierId <= 0 || blank($invoiceNumber)) {
            return;
        }

        $exists = PurchaseInvoice::query()
            ->where('supplier_id', $supplierId)
            ->where('invoice_number', $invoiceNumber)
            ->when(
                $excludingInvoiceId,
                fn ($query) => $query->whereKeyNot($excludingInvoiceId),
            )
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'invoice_number' => 'رقم فاتورة المورد مستخدم بالفعل لنفس المورد.',
            ]);
        }
    }

    /** @param array<int, array<string, mixed>> $items */
    private function applyTaxTotals(array &$data, array &$items): void
    {
        $taxType = TaxType::tryFrom((string) ($data['tax_type'] ?? TaxType::None->value));

        if (! $taxType) {
            throw ValidationException::withMessages([
                'tax_type' => 'نوع الضريبة المحدد غير صالح.',
            ]);
        }

        $subtotal = round((float) collect($items)->sum('total_cost'), 2);
        $discount = min(
            $subtotal,
            max(0, round((float) ($data['discount_amount'] ?? 0), 2))
        );

        $taxTotal = 0.0;

        foreach ($items as &$item) {
            $base = max(0, (float) ($item['total_cost'] ?? 0));

            $discountShare = ($discount > 0 && $subtotal > 0)
                ? round($discount * ($base / $subtotal), 2)
                : 0.0;

            $tax = (bool) ($item['tax_exempt'] ?? false)
                ? 0.0
                : (float) app(DocumentTaxCalculator::class)
                    ->calculate($base, min($base, $discountShare), $taxType)['tax_amount'];

            $item['tax_exempt'] = (bool) ($item['tax_exempt'] ?? false);
            $item['tax_amount'] = round($tax, 2);
            $taxTotal += $tax;
        }
        unset($item);

        $data['discount_amount'] = round($discount, 2);
        $data['tax_type'] = $taxType->value;
        $data['tax_amount'] = round($taxTotal, 2);

        unset($data['total'], $data['subtotal']);
    }
}
