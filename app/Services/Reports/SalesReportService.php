<?php

namespace App\Services\Reports;

use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SalesReportService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function query(array $filters = []): Builder
    {
        return SalesInvoice::query()
            ->with([
                'customer',
                'warehouse',
            ])
            ->withSum('items as items_subtotal', 'line_total')
            ->when(
                filled($filters['date_from'] ?? null),
                fn (Builder $query): Builder => $query->whereDate(
                    'invoice_date',
                    '>=',
                    $filters['date_from']
                )
            )
            ->when(
                filled($filters['date_until'] ?? null),
                fn (Builder $query): Builder => $query->whereDate(
                    'invoice_date',
                    '<=',
                    $filters['date_until']
                )
            )
            ->when(
                filled($filters['customer_id'] ?? null),
                fn (Builder $query): Builder => $query->where(
                    'customer_id',
                    $filters['customer_id']
                )
            )
            ->when(
                filled($filters['warehouse_id'] ?? null),
                fn (Builder $query): Builder => $query->where(
                    'warehouse_id',
                    $filters['warehouse_id']
                )
            )
            ->when(
                filled($filters['payment_type'] ?? null),
                fn (Builder $query): Builder => $query->where(
                    'payment_type',
                    $filters['payment_type']
                )
            )
            ->when(
                filled($filters['document_number'] ?? null),
                fn (Builder $query): Builder => $query->where(
                    'document_number',
                    'like',
                    '%'.trim((string) $filters['document_number']).'%'
                )
            )
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');
    }

    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, SalesInvoice>
     */
    public function records(array $filters = []): Collection
    {
        return $this->query($filters)->get();
    }

    /**
     * @param Collection<int, SalesInvoice> $records
     * @return array{
     *     invoices_count: int,
     *     subtotal: float,
     *     discount: float,
     *     tax: float,
     *     total: float
     * }
     */
    public function totals(Collection $records): array
    {
        return [
            'invoices_count' => $records->count(),

            'subtotal' => (float) $records->sum(
                fn (SalesInvoice $invoice): float =>
                    (float) ($invoice->items_subtotal ?? 0)
            ),

            'discount' => (float) $records->sum(
                fn (SalesInvoice $invoice): float =>
                    (float) ($invoice->discount_amount ?? 0)
            ),

            'tax' => (float) $records->sum(
                fn (SalesInvoice $invoice): float =>
                    (float) ($invoice->tax_amount ?? 0)
            ),

            'total' => (float) $records->sum(
                fn (SalesInvoice $invoice): float =>
                    $invoice->totalAmount()
            ),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     records: Collection<int, SalesInvoice>,
     *     totals: array<string, int|float>
     * }
     */
    public function report(array $filters = []): array
    {
        $records = $this->records($filters);

        return [
            'records' => $records,
            'totals' => $this->totals($records),
        ];
    }
}
