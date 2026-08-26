<?php

namespace App\Services;

use App\Models\PurchaseInvoiceItem;
use App\Models\SalesInvoiceItem;
use Illuminate\Support\Collection;

class PurchaseItemSalesTrackingService
{
    public function report(array $filters = []): array
    {
        /*
         * مبيعات كل صنف مرتبة زمنياً.
         * سنستخدم الكميات مرة واحدة فقط ونوزعها على المشتريات FIFO.
         */
        $salesByItem = SalesInvoiceItem::query()
            ->with([
                'invoice.customer',
                'item',
            ])
            ->whereHas('invoice')
            ->get()
            ->sortBy(function (SalesInvoiceItem $line): string {
                $date = $line->invoice?->invoice_date?->format('Y-m-d') ?? '9999-12-31';

                return $date . '-' . str_pad((string) $line->invoice_id, 12, '0', STR_PAD_LEFT)
                    . '-' . str_pad((string) $line->id, 12, '0', STR_PAD_LEFT);
            })
            ->groupBy('item_id')
            ->map(function (Collection $lines): array {
                return $lines->map(function (SalesInvoiceItem $line): array {
                    return [
                        'line' => $line,
                        'remaining' => (float) $line->quantity,
                    ];
                })->values()->all();
            })
            ->all();

        $purchaseQuery = PurchaseInvoiceItem::query()
            ->with([
                'invoice.supplier',
                'item',
            ])
            ->whereHas('invoice')
            ->orderBy('purchase_invoice_id')
            ->orderBy('id');

        if (! empty($filters['supplier_id'])) {
            $purchaseQuery->whereHas(
                'invoice',
                fn ($query) => $query->where(
                    'supplier_id',
                    $filters['supplier_id']
                )
            );
        }

        if (! empty($filters['item_id'])) {
            $purchaseQuery->where(
                'item_id',
                $filters['item_id']
            );
        }

        if (! empty($filters['from_date'])) {
            $purchaseQuery->whereHas(
                'invoice',
                fn ($query) => $query->whereDate(
                    'invoice_date',
                    '>=',
                    $filters['from_date']
                )
            );
        }

        if (! empty($filters['to_date'])) {
            $purchaseQuery->whereHas(
                'invoice',
                fn ($query) => $query->whereDate(
                    'invoice_date',
                    '<=',
                    $filters['to_date']
                )
            );
        }

        $purchaseLines = $purchaseQuery
            ->get()
            ->sortBy(function (PurchaseInvoiceItem $line): string {
                $date = $line->invoice?->invoice_date?->format('Y-m-d') ?? '9999-12-31';

                return $date . '-' . str_pad((string) $line->purchase_invoice_id, 12, '0', STR_PAD_LEFT)
                    . '-' . str_pad((string) $line->id, 12, '0', STR_PAD_LEFT);
            });

        $rows = [];

        foreach ($purchaseLines as $purchaseLine) {
            $purchaseQty = max(0, (float) $purchaseLine->quantity);
            $remainingPurchaseQty = $purchaseQty;
            $allocations = [];

            $purchaseDate = $purchaseLine->invoice?->invoice_date;

            if ($remainingPurchaseQty > 0 && isset($salesByItem[$purchaseLine->item_id])) {
                foreach ($salesByItem[$purchaseLine->item_id] as &$saleBucket) {
                    if ($remainingPurchaseQty <= 0) {
                        break;
                    }

                    /** @var SalesInvoiceItem $saleLine */
                    $saleLine = $saleBucket['line'];

                    if ($saleBucket['remaining'] <= 0) {
                        continue;
                    }

                    $saleDate = $saleLine->invoice?->invoice_date;

                    /*
                     * لا نربط بيعاً بتاريخ أقدم من تاريخ الشراء.
                     */
                    if (
                        $purchaseDate
                        && $saleDate
                        && $saleDate->lt($purchaseDate)
                    ) {
                        continue;
                    }

                    $allocatedQty = min(
                        $remainingPurchaseQty,
                        $saleBucket['remaining']
                    );

                    if ($allocatedQty <= 0) {
                        continue;
                    }

                    $allocations[] = [
                        'sales_invoice_id' => $saleLine->sales_invoice_id,
                        'document_number' => $saleLine->invoice?->document_number,
                        'invoice_date' => $saleDate?->format('d/m/Y'),
                        'customer' => $saleLine->invoice?->customer?->name ?: '—',
                        'quantity' => round($allocatedQty, 2),
                        'unit_price' => (float) $saleLine->unit_price,
                        'line_total' => round(
                            $allocatedQty * (float) $saleLine->unit_price,
                            2
                        ),
                    ];

                    $saleBucket['remaining'] -= $allocatedQty;
                    $remainingPurchaseQty -= $allocatedQty;
                }

                unset($saleBucket);
            }

            $soldQty = round(
                $purchaseQty - $remainingPurchaseQty,
                2
            );

            $remainingQty = round(
                max(0, $remainingPurchaseQty),
                2
            );

            $status = match (true) {
                $soldQty <= 0 => 'not_sold',
                $remainingQty <= 0 => 'fully_sold',
                default => 'partially_sold',
            };

            if (
                ! empty($filters['status'])
                && $filters['status'] !== $status
            ) {
                continue;
            }

            $customers = collect($allocations)
                ->groupBy('customer')
                ->map(
                    fn (Collection $lines, string $customer): string =>
                        $customer . ' (' .
                        number_format(
                            (float) $lines->sum('quantity'),
                            2
                        ) .
                        ')'
                )
                ->values()
                ->all();

            $rows[] = [
                'purchase_item_id' => $purchaseLine->id,
                'purchase_invoice_id' => $purchaseLine->purchase_invoice_id,
                'purchase_document' => $purchaseLine->invoice?->code ?: '—',
                'purchase_invoice_number' => $purchaseLine->invoice?->invoice_number ?: '—',
                'purchase_date' => $purchaseDate?->format('d/m/Y') ?: '—',
                'supplier' => $purchaseLine->invoice?->supplier?->name ?: '—',

                'item_id' => $purchaseLine->item_id,
                'item_code' => $purchaseLine->item?->code ?: '—',
                'item_name' => $purchaseLine->item?->name ?: '—',

                'purchase_quantity' => $purchaseQty,
                'unit_cost' => (float) $purchaseLine->unit_cost,

                'sold_quantity' => $soldQty,
                'remaining_quantity' => $remainingQty,

                'status' => $status,
                'status_label' => match ($status) {
                    'fully_sold' => 'تم البيع بالكامل',
                    'partially_sold' => 'بيع جزئي',
                    default => 'لم يتم البيع',
                },

                'customers' => $customers,
                'allocations' => $allocations,
            ];
        }

        return [
            'rows' => array_values($rows),

            'summary' => [
                'purchase_quantity' => round(
                    collect($rows)->sum('purchase_quantity'),
                    2
                ),
                'sold_quantity' => round(
                    collect($rows)->sum('sold_quantity'),
                    2
                ),
                'remaining_quantity' => round(
                    collect($rows)->sum('remaining_quantity'),
                    2
                ),
                'fully_sold' => collect($rows)
                    ->where('status', 'fully_sold')
                    ->count(),
                'partially_sold' => collect($rows)
                    ->where('status', 'partially_sold')
                    ->count(),
                'not_sold' => collect($rows)
                    ->where('status', 'not_sold')
                    ->count(),
            ],
        ];
    }
}
