<?php

namespace App\Services;

use App\Models\CustomerPurchaseOrder;
use App\Models\SalesQuotation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesQuotationToCustomerPurchaseOrderService
{
    public function __construct(
        private readonly CustomerPurchaseOrderService $customerPurchaseOrderService,
    ) {
    }

    /**
     * تحويل عرض السعر إلى أمر توريد عميل.
     */
    public function convert(SalesQuotation $salesQuotation): CustomerPurchaseOrder
    {
        return DB::transaction(function () use ($salesQuotation): CustomerPurchaseOrder {
            $quotation = SalesQuotation::query()
                ->with([
                    'customer',
                    'items.item',
                    'items.unit',
                ])
                ->lockForUpdate()
                ->findOrFail($salesQuotation->getKey());

            $existingPurchaseOrder = CustomerPurchaseOrder::query()
                ->where('sales_quotation_id', $quotation->getKey())
                ->first();

            if ($existingPurchaseOrder) {
                return $existingPurchaseOrder;
            }

            if (! $quotation->customer_id) {
                throw new RuntimeException(
                    'لا يمكن تحويل عرض السعر إلى أمر توريد لعدم وجود عميل.'
                );
            }

            if ($quotation->items->isEmpty()) {
                throw new RuntimeException(
                    'لا يمكن تحويل عرض سعر لا يحتوي على بنود.'
                );
            }

            $items = $quotation->items
                ->sortBy('id')
                ->values()
                ->map(function ($quotationItem, int $index): array {
                    $quantity = (float) $quotationItem->quantity;
                    $taxAmount = (float) ($quotationItem->tax_amount ?? 0);
                    $lineTotal = (float) ($quotationItem->line_total ?? 0);
                    $lineSubtotal = max(0, $lineTotal - $taxAmount);

                    return [
                        'item_id' => $quotationItem->item_id,
                        'description' => $quotationItem->item?->name,
                        'unit_id' => $quotationItem->unit_id,
                        'ordered_quantity' => $quantity,
                        'executed_quantity' => 0,
                        'remaining_quantity' => $quantity,
                        'unit_price' => (float) $quotationItem->unit_price,
                        'tax_rate' => 0,
                        'line_subtotal' => $lineSubtotal,
                        'line_tax' => $taxAmount,
                        'line_total' => $lineTotal,
                        'notes' => $quotationItem->notes,
                        'sort_order' => $index + 1,
                    ];
                })
                ->all();

            $purchaseOrder = $this->customerPurchaseOrderService->create([
                'sales_quotation_id' => $quotation->getKey(),
                'customer_id' => $quotation->customer_id,
                'order_date' => now()->toDateString(),
                'received_date' => now()->toDateString(),
                'status' => CustomerPurchaseOrder::STATUS_NEW,
                'execution_percentage' => 0,
                'notes' => $this->buildNotes($quotation),
                'items' => $items,
            ]);

            return $purchaseOrder->fresh([
                'customer',
                'salesQuotation',
                'items.item',
                'items.unit',
                'attachments',
                'followUps',
            ]);
        });
    }

    /**
     * معرفة أمر التوريد الناتج عن عرض السعر، إن وجد.
     */
    public function findPurchaseOrder(
        SalesQuotation $salesQuotation
    ): ?CustomerPurchaseOrder {
        return CustomerPurchaseOrder::query()
            ->where('sales_quotation_id', $salesQuotation->getKey())
            ->first();
    }

    /**
     * التأكد هل تم تحويل عرض السعر مسبقًا.
     */
    public function hasBeenConverted(SalesQuotation $salesQuotation): bool
    {
        return CustomerPurchaseOrder::query()
            ->where('sales_quotation_id', $salesQuotation->getKey())
            ->exists();
    }

    private function buildNotes(SalesQuotation $salesQuotation): string
    {
        $reference = 'تم إنشاؤه من عرض السعر رقم: '
            .$salesQuotation->quotation_number;

        $quotationNotes = trim((string) $salesQuotation->notes);

        if ($quotationNotes === '') {
            return $reference;
        }

        return $reference.PHP_EOL.$quotationNotes;
    }
}