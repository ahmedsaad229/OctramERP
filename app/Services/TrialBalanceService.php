<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class TrialBalanceService
{
    /** @return array<string,mixed> */
    public function report(?string $fromDate, ?string $toDate, bool $movementsOnly = true): array
    {
        $rows = Account::query()
            ->where('allow_posting', true)
            ->where('active', true)
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($fromDate, $toDate): array {
                $base = JournalEntryLine::query()
                    ->where('account_id', $account->getKey())
                    ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id');

                $openingDebit = 0.0; $openingCredit = 0.0;
                if ($fromDate) {
                    $openingDebit = (float) (clone $base)->whereDate('journal_entries.entry_date', '<', $fromDate)->sum('journal_entry_lines.debit');
                    $openingCredit = (float) (clone $base)->whereDate('journal_entries.entry_date', '<', $fromDate)->sum('journal_entry_lines.credit');
                }

                $period = clone $base;
                if ($fromDate) $period->whereDate('journal_entries.entry_date', '>=', $fromDate);
                if ($toDate) $period->whereDate('journal_entries.entry_date', '<=', $toDate);
                $periodDebit = (float) (clone $period)->sum('journal_entry_lines.debit');
                $periodCredit = (float) (clone $period)->sum('journal_entry_lines.credit');

                $openingNet = round($openingDebit - $openingCredit, 2);
                $closingNet = round($openingNet + $periodDebit - $periodCredit, 2);

                return [
                    'id' => $account->getKey(), 'code' => $account->code, 'name' => $account->name,
                    'opening_debit' => max(0, $openingNet), 'opening_credit' => max(0, -$openingNet),
                    'period_debit' => round($periodDebit, 2), 'period_credit' => round($periodCredit, 2),
                    'closing_debit' => max(0, $closingNet), 'closing_credit' => max(0, -$closingNet),
                ];
            });

        if ($movementsOnly) {
            $rows = $rows->filter(fn (array $row): bool =>
                $row['opening_debit'] > 0 || $row['opening_credit'] > 0 ||
                $row['period_debit'] > 0 || $row['period_credit'] > 0 ||
                $row['closing_debit'] > 0 || $row['closing_credit'] > 0
            )->values();
        }

        $totals = [];
        foreach (['opening_debit','opening_credit','period_debit','period_credit','closing_debit','closing_credit'] as $field) {
            $totals[$field] = round((float) $rows->sum($field), 2);
        }
        $totals['balanced'] = abs($totals['closing_debit'] - $totals['closing_credit']) < 0.009;

        return ['rows' => $rows, 'totals' => $totals, 'fromDate' => $fromDate, 'toDate' => $toDate];
    }
}
