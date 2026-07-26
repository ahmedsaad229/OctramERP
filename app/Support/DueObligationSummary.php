<?php

namespace App\Support;

use App\Models\DueObligation;
use Illuminate\Support\Facades\DB;

class DueObligationSummary
{
    /**
     * @return array{customer_due: float, supplier_due: float, due_today: float, overdue: float}
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
                'COALESCE(SUM(CASE WHEN source_type = ? AND payment_type = ? THEN total_amount ELSE 0 END), 0) as supplier_due',
                [DueObligation::TYPE_PURCHASE, 'credit'],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN payment_type = ? AND due_date = ? THEN total_amount ELSE 0 END), 0) as due_today',
                ['credit', $today],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN payment_type = ? AND due_date < ? THEN total_amount ELSE 0 END), 0) as overdue',
                ['credit', $today],
            )
            ->first();

        return [
            'customer_due' => (float) $summary->customer_due,
            'supplier_due' => (float) $summary->supplier_due,
            'due_today' => (float) $summary->due_today,
            'overdue' => (float) $summary->overdue,
        ];
    }
}
