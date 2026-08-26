<?php

namespace App\Services;

use App\Enums\PaymentType;
use App\Models\DueObligation;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DueObligationService
{
    /**
     * إنشاء استعلام الاستحقاقات مع تطبيق الفلاتر.
     *
     * @param array<string, mixed> $filters
     */
    public function query(array $filters = []): Builder
    {
        return DueObligation::queryUnified()
            ->when(
                filled($filters['source_type'] ?? null),
                fn (Builder $query): Builder => $query->where(
                    'source_type',
                    $filters['source_type']
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
                filled($filters['warehouse_id'] ?? null),
                fn (Builder $query): Builder => $query->where(
                    'warehouse_id',
                    $filters['warehouse_id']
                )
            )
            ->when(
                filled($filters['party'] ?? null),
                function (Builder $query) use ($filters): Builder {
                    [$type, $id] = array_pad(
                        explode(':', (string) $filters['party'], 2),
                        2,
                        null
                    );

                    return $query
                        ->when(
                            filled($type),
                            fn (Builder $query): Builder =>
                                $query->where('source_type', $type)
                        )
                        ->when(
                            filled($id),
                            fn (Builder $query): Builder =>
                                $query->where('party_id', $id)
                        );
                }
            )
            ->when(
                filled($filters['date_from'] ?? null),
                fn (Builder $query): Builder => $query->whereDate(
                    'due_date',
                    '>=',
                    $filters['date_from']
                )
            )
            ->when(
                filled($filters['date_until'] ?? null),
                fn (Builder $query): Builder => $query->whereDate(
                    'due_date',
                    '<=',
                    $filters['date_until']
                )
            )
            ->when(
                filled($filters['due_status'] ?? null),
                fn (Builder $query): Builder => $this->applyStatusFilter(
                    $query,
                    $filters['due_status']
                )
            )
            ->when(
                filter_var(
                    $filters['overdue'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                ),
                fn (Builder $query): Builder => $this->applyStatusFilter(
                    $query,
                    DueObligation::STATUS_OVERDUE
                )
            )
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderByDesc('source_id');
    }

    /**
     * الحصول على الاستحقاقات وإضافة المسدد والمتبقي الحقيقي.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, DueObligation>
     */
    public function records(array $filters = []): Collection
    {
        $records = $this->query($filters)->get();

        if ($records->isEmpty()) {
            return $records;
        }

        $salesIds = $records
            ->where('source_type', DueObligation::TYPE_SALE)
            ->pluck('source_id')
            ->filter()
            ->unique()
            ->values();

        $purchaseIds = $records
            ->where('source_type', DueObligation::TYPE_PURCHASE)
            ->pluck('source_id')
            ->filter()
            ->unique()
            ->values();

        /*
         * تحميل الأصناف والتوزيعات دفعة واحدة لمنع N+1 Queries.
         */
        $salesInvoices = SalesInvoice::query()
            ->with([
                'items',
                'receiptAllocations',
            ])
            ->whereKey($salesIds)
            ->get()
            ->keyBy('id');

        $purchaseInvoices = PurchaseInvoice::query()
            ->with([
                'items',
                'supplierPaymentAllocations',
            ])
            ->whereKey($purchaseIds)
            ->get()
            ->keyBy('id');

        return $records->map(
            function (DueObligation $record) use (
                $salesInvoices,
                $purchaseInvoices
            ): DueObligation {
                if ($record->source_type === DueObligation::TYPE_SALE) {
                    /** @var SalesInvoice|null $invoice */
                    $invoice = $salesInvoices->get($record->source_id);

                    if ($invoice) {
                        $total = $invoice->totalAmount();

                        $paid = $record->payment_type === PaymentType::Cash->value
                            ? $total
                            : (float) $invoice
                                ->receiptAllocations
                                ->sum('amount');

                        $record->setAttribute(
                            'total_amount',
                            round($total, 2)
                        );

                        $record->setAttribute(
                            'paid_amount',
                            round($paid, 2)
                        );

                        $record->setAttribute(
                            'remaining_amount',
                            round(max(0, $total - $paid), 2)
                        );

                        $record->setAttribute(
                            'payment_status',
                            $this->paymentStatus($total, $paid)
                        );
                    }

                    return $record;
                }

                if ($record->source_type === DueObligation::TYPE_PURCHASE) {
                    /** @var PurchaseInvoice|null $invoice */
                    $invoice = $purchaseInvoices->get($record->source_id);

                    if ($invoice) {
                        $total = $invoice->totalAmount();

                        $paid = $record->payment_type === PaymentType::Cash->value
                            ? $total
                            : (float) $invoice
                                ->supplierPaymentAllocations
                                ->sum('amount');

                        $record->setAttribute(
                            'total_amount',
                            round($total, 2)
                        );

                        $record->setAttribute(
                            'paid_amount',
                            round($paid, 2)
                        );

                        $record->setAttribute(
                            'remaining_amount',
                            round(max(0, $total - $paid), 2)
                        );

                        $record->setAttribute(
                            'payment_status',
                            $this->paymentStatus($total, $paid)
                        );
                    }
                }

                return $record;
            }
        );
    }

    /**
     * @param Collection<int, DueObligation> $records
     * @return array{
     *     documents_count: int,
     *     total_amount: float,
     *     paid_amount: float,
     *     remaining_amount: float,
     *     fully_paid_count: int,
     *     partially_paid_count: int,
     *     unpaid_count: int
     * }
     */
    public function totals(Collection $records): array
    {
        return [
            'documents_count' => $records->count(),

            'total_amount' => round(
                (float) $records->sum('total_amount'),
                2
            ),

            'paid_amount' => round(
                (float) $records->sum('paid_amount'),
                2
            ),

            'remaining_amount' => round(
                (float) $records->sum('remaining_amount'),
                2
            ),

            'fully_paid_count' => $records
                ->where('payment_status', 'paid')
                ->count(),

            'partially_paid_count' => $records
                ->where('payment_status', 'partially_paid')
                ->count(),

            'unpaid_count' => $records
                ->where('payment_status', 'unpaid')
                ->count(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     records: Collection<int, DueObligation>,
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

    private function paymentStatus(float $total, float $paid): string
    {
        if ($paid <= 0) {
            return 'unpaid';
        }

        return max(0, $total - $paid) > 0.009
            ? 'partially_paid'
            : 'paid';
    }

    private function applyStatusFilter(
        Builder $query,
        ?string $status
    ): Builder {
        $today = now()->toDateString();

        return match ($status) {
            DueObligation::STATUS_TODAY => $query
                ->where('payment_type', 'credit')
                ->whereDate('due_date', $today),

            DueObligation::STATUS_FUTURE => $query
                ->where('payment_type', 'credit')
                ->whereDate('due_date', '>', $today),

            DueObligation::STATUS_OVERDUE => $query
                ->where('payment_type', 'credit')
                ->whereDate('due_date', '<', $today),

            DueObligation::STATUS_CASH => $query
                ->where(function (Builder $query): void {
                    $query
                        ->where('payment_type', 'cash')
                        ->orWhereNull('due_date');
                }),

            default => $query,
        };
    }
}
