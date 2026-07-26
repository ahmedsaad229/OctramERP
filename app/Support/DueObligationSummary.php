<?php

namespace App\Support;

use App\Models\DueObligation;
use Illuminate\Support\Facades\DB;

class DueObligationSummary
{
    /**
     * @return array{
     *     customer_due: float,
     *     customer_due_count: int,
     *     supplier_due: float,
     *     supplier_due_count: int,
     *     due_today: float,
     *     due_today_count: int,
     *     overdue: float,
     *     overdue_count: int
     * }
     */
    public static function totals(): array
    {
        $today = now()->toDateString();
        $summary = DB::query()
            ->fromSub(DueObligation::unifiedQuery(), 'due_obligations')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN source_type = ? AND payment_type = ? THEN total_amount ELSE 0 END), 0) as customer_due',
                [DueObligation::TYPE_SALE, 'credit'],
            )
            ->selectRaw(
                'SUM(CASE WHEN source_type = ? AND payment_type = ? THEN 1 ELSE 0 END) as customer_due_count',
                [DueObligation::TYPE_SALE, 'credit'],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN source_type = ? AND payment_type = ? THEN total_amount ELSE 0 END), 0) as supplier_due',
                [DueObligation::TYPE_PURCHASE, 'credit'],
            )
            ->selectRaw(
                'SUM(CASE WHEN source_type = ? AND payment_type = ? THEN 1 ELSE 0 END) as supplier_due_count',
                [DueObligation::TYPE_PURCHASE, 'credit'],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN payment_type = ? AND due_date = ? THEN total_amount ELSE 0 END), 0) as due_today',
                ['credit', $today],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_type = ? AND due_date = ? THEN 1 ELSE 0 END) as due_today_count',
                ['credit', $today],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN payment_type = ? AND due_date < ? THEN total_amount ELSE 0 END), 0) as overdue',
                ['credit', $today],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_type = ? AND due_date < ? THEN 1 ELSE 0 END) as overdue_count',
                ['credit', $today],
            )
            ->first();

        return [
            'customer_due' => (float) $summary->customer_due,
            'customer_due_count' => (int) $summary->customer_due_count,
            'supplier_due' => (float) $summary->supplier_due,
            'supplier_due_count' => (int) $summary->supplier_due_count,
            'due_today' => (float) $summary->due_today,
            'due_today_count' => (int) $summary->due_today_count,
            'overdue' => (float) $summary->overdue,
            'overdue_count' => (int) $summary->overdue_count,
        ];
    }
}
