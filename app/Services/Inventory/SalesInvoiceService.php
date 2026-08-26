<?php

namespace App\Services\Inventory;

use App\Enums\TaxType;
use App\Models\Item;
use App\Models\PartyTransaction;
use App\Models\SalesInvoice;
use App\Models\SalesQuotation;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Unit;
use App\Services\CompanyTaxSetting;
use App\Services\CustomerPurchaseOrderService;
use App\Services\Documents\DocumentDeletionGuard;
use App\Services\DocumentTaxCalculator;
use App\Services\PartyTransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SalesInvoiceService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly PartyTransactionService $partyTransactionService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SalesInvoice
    {
        $data['tax_type'] ??= app(CompanyTaxSetting::class)->resolve()->value;

        return DB::transaction(function () use ($data): SalesInvoice {
            $this->validateElectronicInvoiceNumber($data);
            $this->validateQuotationLink($data);
            $items = $this->normalizeItems($data['items'] ?? []);
            $this->validateWarehouseRequirement($data, $items);
            $this->applyTaxTotals($data, $items);
            $this->applyServiceTaxDiscount($data, $items);
            $this->applyOnePercentDiscount($data, $items);
            $this->validateAvailableStock((int) ($data['warehouse_id'] ?? 0), $items);
            unset($data['items'], $data['document_number']);

            $invoice = SalesInvoice::create($data);
            $invoice->items()->createMany($items);

            $invoice = $this->post($invoice);
            app(CustomerPurchaseOrderService::class)->replaceSalesInvoiceExecutions($invoice);

            return $invoice->fresh(['items', 'customer', 'warehouse']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SalesInvoice $invoice, array $data): SalesInvoice
    {
        return DB::transaction(function () use ($invoice, $data): SalesInvoice {
            $this->validateElectronicInvoiceNumber($data);
            $invoice = SalesInvoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            $data['tax_type'] ??= $invoice->tax_type->value;
            $this->validateQuotationLink($data, (int) $invoice->getKey());
            $items = $this->normalizeItems($data['items'] ?? []);
            $this->validateWarehouseRequirement($data, $items);
            $this->applyTaxTotals($data, $items);
            $this->applyServiceTaxDiscount($data, $items);
            $this->applyOnePercentDiscount($data, $items);
            $this->validateAvailableStock(
                (int) ($data['warehouse_id'] ?? 0),
                $items,
                (int) $invoice->getKey(),
            );
            unset($data['items'], $data['document_number']);

            $invoice->update($data);
            $invoice->items()->delete();
            $invoice->items()->createMany($items);

            $invoice = $this->post($invoice);
            app(CustomerPurchaseOrderService::class)->replaceSalesInvoiceExecutions($invoice);

            return $invoice->fresh(['items', 'customer', 'warehouse']);
        });
    }

    public function delete(SalesInvoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice): bool {
            $invoice = SalesInvoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            app(DocumentDeletionGuard::class)->assertCanDelete($invoice);

            $this->inventoryService->deleteDocumentTransactions($invoice->document_number);
            $this->partyTransactionService->deleteDocumentTransaction($invoice);
            app(\App\Services\JournalEntryService::class)->deleteForSource($invoice);
            app(CustomerPurchaseOrderService::class)->removeSalesInvoiceExecutions($invoice);
            $invoice->items()->delete();

            return (bool) $invoice->delete();
        });
    }

    private function post(SalesInvoice $invoice): SalesInvoice
    {
        $invoice->load('items.item', 'customer');

        $transactions = [];

        foreach ($invoice->items as $invoiceItem) {
            if ($invoiceItem->item?->isNonStockItem()) {
                continue;
            }

            $averageCost = StockBalance::query()
                ->where('warehouse_id', $invoice->warehouse_id)
                ->where('item_id', $invoiceItem->item_id)
                ->lockForUpdate()
                ->value('average_cost') ?? 0;

            $transactions[] = [
                'warehouse_id' => $invoice->warehouse_id,
                'item_id' => $invoiceItem->item_id,
                'transaction_type' => StockTransaction::TYPE_SALE,
                'quantity' => $invoiceItem->quantity,
                'unit_cost' => $averageCost,
                'transaction_date' => $invoice->invoice_date,
                'notes' => $invoice->notes,
            ];
        }

        $this->inventoryService->replaceDocumentTransactions(
            $invoice->document_number,
            $transactions,
        );

        $invoiceTotal = $invoice->totalAmount();

        $this->partyTransactionService->replaceDocumentTransaction(
            $invoice->customer,
            PartyTransaction::TYPE_CUSTOMER_DEBIT,
            $invoice,
            $invoice->invoice_date,
            $invoiceTotal,
            0,
            $invoice->document_number,
            $invoice->notes,
        );

        app(\App\Services\JournalEntryService::class)->postSalesInvoice($invoice);

        return $invoice->fresh(['items', 'customer', 'warehouse']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];
        $seenOrderItems = [];

        foreach ($items as $item) {
            $itemId = (int) ($item['item_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            if ($itemId <= 0 || $quantity <= 0 || $unitPrice < 0) {
                throw ValidationException::withMessages([
                    'items' => 'بيانات أصناف الفاتورة غير صحيحة.',
                ]);
            }

            $inventoryItem = Item::query()->find($itemId);
            if (! $inventoryItem || ! $inventoryItem->active) {
                throw ValidationException::withMessages(['items' => 'أحد بنود الفاتورة غير موجود أو غير نشط.']);
            }

            $unitId = filled($item['unit_id'] ?? null)
                ? (int) $item['unit_id']
                : ($inventoryItem->isStockItem() ? (int) $inventoryItem->unit_id : null);
            if ($inventoryItem->isStockItem() && (! $inventoryItem->unit_id || $unitId !== (int) $inventoryItem->unit_id)) {
                throw ValidationException::withMessages(['items' => 'يجب تحديد الوحدة الافتراضية الصحيحة لكل بند يؤثر على المخزون.']);
            }
            if ($inventoryItem->isNonStockItem() && $unitId && ! Unit::query()->whereKey($unitId)->exists()) {
                throw ValidationException::withMessages(['items' => 'الوحدة المحددة لأحد البنود غير موجودة.']);
            }

            $quotationItemId = filled($item['sales_quotation_item_id'] ?? null)
                ? (int) $item['sales_quotation_item_id']
                : null;
            $orderItemId = filled($item['customer_purchase_order_item_id'] ?? null)
                ? (int) $item['customer_purchase_order_item_id'] : null;
            if ($orderItemId && isset($seenOrderItems[$orderItemId])) {
                throw ValidationException::withMessages(['items' => 'لا يمكن تكرار نفس بند أمر التوريد داخل الفاتورة.']);
            }
            if ($orderItemId) {
                $seenOrderItems[$orderItemId] = true;
            }
            $key = $orderItemId ? "order-{$orderItemId}" : ($quotationItemId ? "quotation-{$quotationItemId}" : "item-{$itemId}");

            if (! isset($normalized[$key])) {
                $normalized[$key] = [
                    'item_id' => $itemId,
                    'sales_quotation_item_id' => $quotationItemId,
                    'unit_id' => $inventoryItem->isNonStockItem() ? $unitId : (int) $inventoryItem->unit_id,
                    'quantity' => 0.0,
                    'unit_price' => $unitPrice,
                    'discount_amount' => (float) ($item['discount_amount'] ?? 0),
                    'tax_exempt' => (bool) ($item['tax_exempt'] ?? false),
                    'tax_amount' => (float) ($item['tax_amount'] ?? 0),
                    'line_total' => 0.0,
                    'notes' => $item['notes'] ?? null,
                ];
                if ($orderItemId) {
                    $normalized[$key]['customer_purchase_order_item_id'] = $orderItemId;
                }
            }

            if ((float) $normalized[$key]['unit_price'] !== $unitPrice) {
                throw ValidationException::withMessages([
                    'items' => 'لا يمكن تكرار الصنف بأسعار بيع مختلفة.',
                ]);
            }

            $normalized[$key]['quantity'] += $quantity;
            $normalized[$key]['line_total'] = round(
                $normalized[$key]['quantity'] * $unitPrice,
                2,
            );
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'items' => 'يجب إضافة صنف واحد على الأقل.',
            ]);
        }

        return array_values($normalized);
    }

    private function validateQuotationLink(array $data, ?int $excludingSalesInvoiceId = null): void
    {
        $quotationId = (int) ($data['sales_quotation_id'] ?? 0);
        if ($quotationId <= 0) {
            foreach ($data['items'] ?? [] as $item) {
                if (filled($item['sales_quotation_item_id'] ?? null)) {
                    throw ValidationException::withMessages(['sales_quotation_id' => 'يجب اختيار عرض السعر المرتبط.']);
                }
            }

            return;
        }

        $quotation = SalesQuotation::query()->with('items')->find($quotationId);
        if (! $quotation || (int) $quotation->customer_id !== (int) ($data['customer_id'] ?? 0)) {
            throw ValidationException::withMessages(['sales_quotation_id' => 'عرض السعر لا يخص العميل المحدد.']);
        }

        foreach ($data['items'] ?? [] as $index => $row) {
            if (blank($row['sales_quotation_item_id'] ?? null)) {
                continue;
            }
            $quotationItem = $quotation->items->firstWhere('id', (int) $row['sales_quotation_item_id']);
            if (! $quotationItem || (int) $quotationItem->item_id !== (int) ($row['item_id'] ?? 0)) {
                throw ValidationException::withMessages(["items.{$index}" => 'الصنف لا ينتمي إلى عرض السعر المحدد.']);
            }
            if ((float) ($row['quantity'] ?? 0) > $quotationItem->remainingQuantity($excludingSalesInvoiceId)) {
                throw ValidationException::withMessages(["items.{$index}.quantity" => 'الكمية تتجاوز الكمية المتبقية في عرض السعر.']);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function validateAvailableStock(
        int $warehouseId,
        array $items,
        ?int $salesInvoiceId = null,
    ): void {
        foreach ($items as $item) {
            if (Item::query()->find($item['item_id'])?->isNonStockItem()) {
                continue;
            }

            $availableQuantity = $this->inventoryService->availableForSalesInvoice(
                $warehouseId,
                (int) $item['item_id'],
                $salesInvoiceId,
            );

            if ((float) $item['quantity'] > $availableQuantity) {
                throw ValidationException::withMessages([
                    'items' => 'الكمية المطلوبة غير متوفرة في المخزن.',
                ]);
            }
        }
    }

    /** @param array<int, array<string, mixed>> $items */
    private function validateWarehouseRequirement(array $data, array $items): void
    {
        $hasStockItems = Item::query()
            ->whereIn('id', collect($items)->pluck('item_id'))
            ->where('is_stock_item', true)
            ->exists();

        if ($hasStockItems && blank($data['warehouse_id'] ?? null)) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'يجب تحديد المخزن عند وجود بنود مخزنية.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    /**
     * خصم ضريبة خدمات 3% من صافي الفاتورة قبل ضريبة القيمة المضافة.
     *
     * @param array<string,mixed> $data
     * @param array<int,array<string,mixed>> $items
     */
    /**
     * خصم وإضافة 1% من صافي الفاتورة قبل ضريبة القيمة المضافة.
     *
     * @param array<string,mixed> $data
     * @param array<int,array<string,mixed>> $items
     */
    private function applyOnePercentDiscount(array &$data, array $items): void
    {
        $enabled = (bool) ($data['one_percent_discount_enabled'] ?? false);

        if (! $enabled) {
            $data['one_percent_discount_enabled'] = false;
            $data['one_percent_discount_amount'] = 0;

            return;
        }

        $subtotal = (float) collect($items)->sum(
            fn (array $item): float => (float) ($item['line_total'] ?? 0)
        );

        $invoiceDiscount = max(
            0,
            (float) ($data['discount_amount'] ?? 0)
        );

        $beforeVat = round(
            max(0, $subtotal - $invoiceDiscount),
            2
        );

        $data['one_percent_discount_enabled'] = true;
        $data['one_percent_discount_amount'] = round(
            $beforeVat * 0.01,
            2
        );
    }

    private function applyServiceTaxDiscount(array &$data, array $items): void
    {
        $enabled = (bool) ($data['service_tax_discount_enabled'] ?? false);

        if (! $enabled) {
            $data['service_tax_discount_enabled'] = false;
            $data['service_tax_discount_amount'] = 0;
            return;
        }

        $subtotal = (float) collect($items)->sum(
            fn (array $item): float => (float) ($item['line_total'] ?? 0)
        );

        $invoiceDiscount = max(
            0,
            (float) ($data['discount_amount'] ?? 0)
        );

        $taxableBeforeVat = round(
            max(0, $subtotal - $invoiceDiscount),
            2
        );

        $data['service_tax_discount_enabled'] = true;
        $data['service_tax_discount_amount'] = round(
            $taxableBeforeVat * 0.03,
            2
        );
    }

    private function validateElectronicInvoiceNumber(array $data): void
    {
        Validator::make(
            $data,
            ['electronic_invoice_number' => ['required', 'integer', 'min:1']],
            [
                'electronic_invoice_number.required' => 'رقم الفاتورة الإلكترونية مطلوب.',
                'electronic_invoice_number.integer' => 'رقم الفاتورة الإلكترونية يجب أن يكون رقماً صحيحاً.',
                'electronic_invoice_number.min' => 'رقم الفاتورة الإلكترونية يجب أن يكون أكبر من صفر.',
            ],
        )->validate();
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

        $subtotal = round((float) collect($items)->sum('line_total'), 2);

        $lineDiscountTotal = round(
            (float) collect($items)->sum(
                fn (array $item): float => min(
                    (float) $item['line_total'],
                    max(0, (float) ($item['discount_amount'] ?? 0))
                )
            ),
            2
        );

        // خصومات البنود القادمة من عرض السعر جزء من خصم الفاتورة.
        // لو هناك خصم إضافي على رأس الفاتورة يتم توزيعه نسبياً على كل البنود،
        // وبالتالي البند المعفى لا يدخل في وعاء الضريبة.
        $discount = min(
            $subtotal,
            max(
                $lineDiscountTotal,
                max(0, round((float) ($data['discount_amount'] ?? 0), 2))
            )
        );

        $additionalDiscount = max(0, $discount - $lineDiscountTotal);

        $netBeforeAdditional = (float) collect($items)->sum(function (array $item): float {
            $base = (float) $item['line_total'];
            $lineDiscount = min($base, max(0, (float) ($item['discount_amount'] ?? 0)));

            return max(0, $base - $lineDiscount);
        });

        $taxTotal = 0.0;

        foreach ($items as &$item) {
            $base = (float) $item['line_total'];
            $lineDiscount = min($base, max(0, (float) ($item['discount_amount'] ?? 0)));
            $lineNet = max(0, $base - $lineDiscount);

            $extraShare = ($additionalDiscount > 0 && $netBeforeAdditional > 0)
                ? round($additionalDiscount * ($lineNet / $netBeforeAdditional), 2)
                : 0.0;

            $extraShare = min($lineNet, $extraShare);

            $tax = (bool) ($item['tax_exempt'] ?? false)
                ? 0.0
                : (float) app(DocumentTaxCalculator::class)
                    ->calculate($lineNet, $extraShare, $taxType)['tax_amount'];

            $item['discount_amount'] = round($lineDiscount, 2);
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
