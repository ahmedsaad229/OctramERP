<?php

namespace App\Services\Inventory;

use App\Enums\TaxType;
use App\Models\PartyTransaction;
use App\Models\SalesInvoice;
use App\Models\SalesQuotation;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Services\CompanyTaxSetting;
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
            $this->applyTaxTotals($data, $items);
            $this->validateAvailableStock((int) ($data['warehouse_id'] ?? 0), $items);
            unset($data['items'], $data['document_number']);

            $invoice = SalesInvoice::create($data);
            $invoice->items()->createMany($items);

            return $this->post($invoice);
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
            $this->applyTaxTotals($data, $items);
            $this->validateAvailableStock(
                (int) ($data['warehouse_id'] ?? 0),
                $items,
                (int) $invoice->getKey(),
            );
            unset($data['items'], $data['document_number']);

            $invoice->update($data);
            $invoice->items()->delete();
            $invoice->items()->createMany($items);

            return $this->post($invoice);
        });
    }

    public function delete(SalesInvoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice): bool {
            $invoice = SalesInvoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            app(DocumentDeletionGuard::class)->assertCanDelete($invoice);

            $this->inventoryService->deleteDocumentTransactions($invoice->document_number);
            $this->partyTransactionService->deleteDocumentTransaction($invoice);
            $invoice->items()->delete();

            return (bool) $invoice->delete();
        });
    }

    private function post(SalesInvoice $invoice): SalesInvoice
    {
        $invoice->load('items', 'customer');

        $transactions = $invoice->items
            ->map(function ($item) use ($invoice): array {
                $averageCost = StockBalance::query()
                    ->where('warehouse_id', $invoice->warehouse_id)
                    ->where('item_id', $item->item_id)
                    ->lockForUpdate()
                    ->value('average_cost') ?? 0;

                return [
                    'warehouse_id' => $invoice->warehouse_id,
                    'item_id' => $item->item_id,
                    'transaction_type' => StockTransaction::TYPE_SALE,
                    'quantity' => $item->quantity,
                    'unit_cost' => $averageCost,
                    'transaction_date' => $invoice->invoice_date,
                    'notes' => $invoice->notes,
                ];
            })
            ->all();

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

        return $invoice->fresh(['items', 'customer', 'warehouse']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $itemId = (int) ($item['item_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            if ($itemId <= 0 || $quantity <= 0 || $unitPrice < 0) {
                throw ValidationException::withMessages([
                    'items' => 'بيانات أصناف الفاتورة غير صحيحة.',
                ]);
            }

            $quotationItemId = filled($item['sales_quotation_item_id'] ?? null)
                ? (int) $item['sales_quotation_item_id']
                : null;
            $key = $quotationItemId ? "quotation-{$quotationItemId}" : "item-{$itemId}";

            if (! isset($normalized[$key])) {
                $normalized[$key] = [
                    'item_id' => $itemId,
                    'sales_quotation_item_id' => $quotationItemId,
                    'unit_id' => $item['unit_id'] ?? null,
                    'quantity' => 0.0,
                    'unit_price' => $unitPrice,
                    'discount_amount' => (float) ($item['discount_amount'] ?? 0),
                    'tax_amount' => (float) ($item['tax_amount'] ?? 0),
                    'line_total' => 0.0,
                    'notes' => $item['notes'] ?? null,
                ];
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

    /**
     * @param  array<string, mixed>  $data
     */
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
    private function applyTaxTotals(array &$data, array $items): void
    {
        $taxType = TaxType::tryFrom((string) ($data['tax_type'] ?? TaxType::None->value));

        if (! $taxType) {
            throw ValidationException::withMessages(['tax_type' => 'نوع الضريبة المحدد غير صالح.']);
        }

        $subtotal = (float) collect($items)->sum('line_total');
        $discount = max(0, round((float) ($data['discount_amount'] ?? 0), 2));
        $calculation = app(DocumentTaxCalculator::class)->calculate($subtotal, $discount, $taxType);
        $data['discount_amount'] = $discount;
        $data['tax_type'] = $taxType->value;
        $data['tax_amount'] = $calculation['tax_amount'];
        unset($data['total'], $data['subtotal']);
    }
}
