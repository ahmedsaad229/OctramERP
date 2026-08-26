<?php

namespace App\Services;

use App\Models\SalesQuotation;

class SalesQuotationConversionService
{
    public function options(?int $customerId = null, ?int $currentQuotationId = null): array
    {
        return SalesQuotation::query()->with('items')->orderByDesc('quotation_date')->get()
            ->filter(fn (SalesQuotation $quotation): bool => ($quotation->getKey() === $currentQuotationId)
                || ((! $customerId || $quotation->customer_id === $customerId) && ! $quotation->isFullyConverted()))
            ->mapWithKeys(fn (SalesQuotation $quotation): array => [
                $quotation->getKey() => "{$quotation->quotation_number} — {$quotation->quotation_date->format('d/m/Y')}",
            ])->all();
    }

    public function payload(int $quotationId, ?int $excludingSalesInvoiceId = null): array
    {
        $quotation = SalesQuotation::query()->with(['items.item.unit'])->findOrFail($quotationId);

        $items = $quotation->items->map(function ($item) use ($excludingSalesInvoiceId): ?array {
            $remaining = $item->remainingQuantity($excludingSalesInvoiceId);
            if ($remaining <= 0) {
                return null;
            }
            $ratio = $remaining / (float) $item->quantity;

            return [
                'sales_quotation_item_id' => $item->getKey(),
                'item_id' => $item->item_id,
                'is_stock_item_state' => $item->item?->is_stock_item,
                'unit_id' => $item->unit_id,
                'quantity' => $remaining,
                'unit_price' => $item->unit_price,
                'discount_type' => $item->discount_type ?? 'value',
                'discount_value' => $item->discount_type === 'percent'
                    ? (float) $item->discount_value
                    : round((float) $item->discount_value, 2),
                'discount_amount' => round((float) $item->discount_amount * $ratio, 2),
                'tax_exempt' => (bool) $item->tax_exempt,
                'tax_amount' => round((float) $item->tax_amount * $ratio, 2),
                'notes' => $item->notes,
            ];
        })->filter()->values();

        return [
            'sales_quotation_id' => $quotation->getKey(),
            'customer_id' => $quotation->customer_id,
            'warehouse_id' => $quotation->warehouse_id,
            'tax_type' => $quotation->tax_type->value,
            'discount_amount' => round((float) $items->sum('discount_amount'), 2),
            'items' => $items->all(),
        ];
    }
}
