<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class IncomeStatementService
{
    /** @return array<string, mixed> */
    public function report(?string $fromDate, ?string $toDate, bool $details = true): array
    {
        $types = [
            Account::TYPE_REVENUE,
            Account::TYPE_COST,
            Account::TYPE_EXPENSE,
            Account::TYPE_OTHER_REVENUE,
            Account::TYPE_OTHER_EXPENSE,
        ];

        $accounts = Account::query()
            ->whereIn('account_type', $types)
            ->where('allow_posting', true)
            ->where('active', true)
            ->orderBy('code')
            ->get();

        $rows = $accounts->map(function (Account $account) use ($fromDate, $toDate): array {
            $query = JournalEntryLine::query()
                ->where('journal_entry_lines.account_id', $account->getKey())
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id');

            if ($fromDate) {
                $query->whereDate('journal_entries.entry_date', '>=', $fromDate);
            }

            if ($toDate) {
                $query->whereDate('journal_entries.entry_date', '<=', $toDate);
            }

            $debit = (float) (clone $query)->sum('journal_entry_lines.debit');
            $credit = (float) (clone $query)->sum('journal_entry_lines.credit');

            $amount = match ($account->account_type) {
                Account::TYPE_REVENUE, Account::TYPE_OTHER_REVENUE => $credit - $debit,
                default => $debit - $credit,
            };

            return [
                'id' => $account->getKey(),
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->account_type,
                'amount' => round($amount, 2),
            ];
        })->filter(fn (array $row): bool => abs($row['amount']) >= 0.005)->values();

        $sections = collect([
            Account::TYPE_REVENUE => $this->section('الإيرادات', Account::TYPE_REVENUE, $rows, $details),
            Account::TYPE_COST => $this->section('تكلفة الإيرادات والمشروعات', Account::TYPE_COST, $rows, $details),
            Account::TYPE_EXPENSE => $this->section('المصروفات التشغيلية والإدارية', Account::TYPE_EXPENSE, $rows, $details),
            Account::TYPE_OTHER_REVENUE => $this->section('إيرادات أخرى', Account::TYPE_OTHER_REVENUE, $rows, $details),
            Account::TYPE_OTHER_EXPENSE => $this->section('مصروفات أخرى', Account::TYPE_OTHER_EXPENSE, $rows, $details),
        ]);

        $revenue = (float) $sections[Account::TYPE_REVENUE]['total'];
        $cost = (float) $sections[Account::TYPE_COST]['total'];
        $expenses = (float) $sections[Account::TYPE_EXPENSE]['total'];
        $otherRevenue = (float) $sections[Account::TYPE_OTHER_REVENUE]['total'];
        $otherExpense = (float) $sections[Account::TYPE_OTHER_EXPENSE]['total'];

        $grossProfit = round($revenue - $cost, 2);
        $operatingProfit = round($grossProfit - $expenses, 2);
        $netProfit = round($operatingProfit + $otherRevenue - $otherExpense, 2);

        return [
            'sections' => $sections,
            'totals' => [
                'revenue' => round($revenue, 2),
                'cost' => round($cost, 2),
                'gross_profit' => $grossProfit,
                'expenses' => round($expenses, 2),
                'operating_profit' => $operatingProfit,
                'other_revenue' => round($otherRevenue, 2),
                'other_expense' => round($otherExpense, 2),
                'net_profit' => $netProfit,
                'is_profit' => $netProfit >= 0,
            ],
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'details' => $details,
        ];
    }

    /** @param Collection<int, array<string,mixed>> $rows */
    private function section(string $label, string $type, Collection $rows, bool $details): array
    {
        $items = $rows->where('type', $type)->values();

        return [
            'label' => $label,
            'type' => $type,
            'rows' => $details ? $items : collect(),
            'total' => round((float) $items->sum('amount'), 2),
        ];
    }
}
