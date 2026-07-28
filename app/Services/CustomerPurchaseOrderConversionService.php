<?php

namespace App\Services;

use App\Models\CustomerPurchaseOrder;
use App\Models\CustomerPurchaseOrderExecution;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CustomerPurchaseOrderConversionService
{
    public function options(?int $customerId, ?int $currentId = null): array
    {
        if (! $customerId || ! Schema::hasTable('customer_purchase_orders')) {
            return [];
        }

        return CustomerPurchaseOrder::query()->where('customer_id', $customerId)
            ->where(function ($query) use ($currentId): void {
                $query->where(function ($eligible): void {
                    $eligible->whereNotIn('status', [CustomerPurchaseOrder::STATUS_CANCELLED, CustomerPurchaseOrder::STATUS_COMPLETED])
                        ->whereHas('items', fn ($items) => $items->where('remaining_quantity', '>', 0));
                })->when($currentId, fn ($query) => $query->orWhereKey($currentId));
            })
            ->orderByDesc('order_date')->get()->mapWithKeys(fn ($order) => [$order->id => trim("{$order->document_number} — {$order->customer_order_number}", ' —')])->all();
    }

    public function lines(int $orderId, ?int $salesInvoiceId = null): array
    {
        $order = CustomerPurchaseOrder::with(['items.item', 'items.unit'])->findOrFail($orderId);
        $current = $salesInvoiceId ? CustomerPurchaseOrderExecution::query()
            ->where('source_type', (new SalesInvoice)->getMorphClass())->where('source_id', $salesInvoiceId)
            ->pluck('executed_quantity', 'customer_purchase_order_item_id') : collect();

        return $order->items->map(function ($line) use ($current): array {
            $remaining = (float) $line->remaining_quantity + (float) ($current[$line->id] ?? 0);

            return [
                'selected' => $remaining > 0, 'customer_purchase_order_item_id' => $line->id,
                'item_id' => $line->item_id, 'item_code' => $line->item->code,
                'item_name' => $line->item->name, 'unit_id' => $line->unit_id,
                'unit_name' => $line->unit?->name, 'ordered_quantity' => (float) $line->ordered_quantity,
                'executed_quantity' => (float) $line->executed_quantity - (float) ($current[$line->id] ?? 0),
                'remaining_quantity' => $remaining, 'import_quantity' => $remaining,
                'unit_price' => (float) ($line->unit_price ?? 0), 'tax_rate' => (float) ($line->tax_rate ?? 0),
                'description' => $line->description,
            ];
        })->filter(fn (array $line): bool => $line['remaining_quantity'] > 0)->values()->all();
    }

    /** @param array<int, array<string, mixed>> $lines
     * @return array<int, array<string, mixed>>
     */
    public function invoiceItems(array $lines): array
    {
        $selected = collect($lines)->where('selected', true);
        if ($selected->isEmpty()) {
            throw ValidationException::withMessages(['order_import_lines' => 'يجب اختيار بند واحد على الأقل للاستيراد.']);
        }
        if ($selected->pluck('customer_purchase_order_item_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['order_import_lines' => 'لا يمكن تكرار نفس بند أمر التوريد.']);
        }

        return $selected->map(function (array $line): array {
            $quantity = (float) ($line['import_quantity'] ?? 0);
            if ($quantity <= 0 || $quantity > (float) ($line['remaining_quantity'] ?? 0)) {
                throw ValidationException::withMessages(['order_import_lines' => 'الكمية المطلوبة تتجاوز الكمية المتبقية في أمر التوريد.']);
            }

            return [
                'customer_purchase_order_item_id' => $line['customer_purchase_order_item_id'],
                'item_id' => $line['item_id'], 'unit_id' => $line['unit_id'],
                'quantity' => $quantity, 'unit_price' => (float) ($line['unit_price'] ?? 0),
                'discount_amount' => 0, 'tax_amount' => 0, 'notes' => $line['description'] ?? null,
            ];
        })->values()->all();
    }
}
