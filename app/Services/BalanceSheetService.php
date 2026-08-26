<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class BalanceSheetService
{
    /** @return array<string, mixed> */
    public function report(?string $asOfDate, bool $details = true): array
    {
        $types = [
            Account::TYPE_ASSET,
            Account::TYPE_LIABILITY,
            Account::TYPE_EQUITY,
        ];

        $accounts = Account::query()
            ->whereIn('account_type', $types)
            ->where('allow_posting', true)
            ->where('active', true)
            ->orderBy('code')
            ->get();

        $rows = $accounts->map(function (Account $account) use ($asOfDate): array {
            $query = JournalEntryLine::query()
                ->where('journal_entry_lines.account_id', $account->getKey())
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id');

            if ($asOfDate) {
                $query->whereDate('journal_entries.entry_date', '<=', $asOfDate);
            }

            $debit = (float) (clone $query)->sum('journal_entry_lines.debit');
            $credit = (float) (clone $query)->sum('journal_entry_lines.credit');

            $amount = match ($account->account_type) {
                Account::TYPE_ASSET => $debit - $credit,
                Account::TYPE_LIABILITY, Account::TYPE_EQUITY => $credit - $debit,
                default => 0.0,
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
            Account::TYPE_ASSET => $this->section('الأصول', Account::TYPE_ASSET, $rows, $details),
            Account::TYPE_LIABILITY => $this->section('الالتزامات', Account::TYPE_LIABILITY, $rows, $details),
            Account::TYPE_EQUITY => $this->section('حقوق الملكية', Account::TYPE_EQUITY, $rows, $details),
        ]);

        $assets = (float) $sections[Account::TYPE_ASSET]['total'];
        $liabilities = (float) $sections[Account::TYPE_LIABILITY]['total'];
        $equityBeforeResult = (float) $sections[Account::TYPE_EQUITY]['total'];

        // Until annual closing is performed, the cumulative result of operations
        // must be presented within equity so the accounting equation remains complete.
        $currentResult = (float) app(IncomeStatementService::class)
            ->report(null, $asOfDate, false)['totals']['net_profit'];

        $equity = round($equityBeforeResult + $currentResult, 2);
        $liabilitiesAndEquity = round($liabilities + $equity, 2);
        $difference = round($assets - $liabilitiesAndEquity, 2);

        return [
            'sections' => $sections,
            'totals' => [
                'assets' => round($assets, 2),
                'liabilities' => round($liabilities, 2),
                'equity_before_result' => round($equityBeforeResult, 2),
                'current_result' => round($currentResult, 2),
                'equity' => $equity,
                'liabilities_and_equity' => $liabilitiesAndEquity,
                'difference' => $difference,
                'balanced' => abs($difference) < 0.01,
                'is_profit' => $currentResult >= 0,
            ],
            'asOfDate' => $asOfDate,
            'details' => $details,
        ];
    }

    /** @param Collection<int, array<string, mixed>> $rows */
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
