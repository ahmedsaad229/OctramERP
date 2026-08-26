<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Support\Collection;

class BankStatementService
{
    public function report(int $bankAccountId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $account = BankAccount::query()->with(['bank', 'ledgerAccount'])->findOrFail($bankAccountId);
        $openingMovement = BankTransaction::query()->where('bank_account_id', $account->getKey())
            ->when($fromDate, fn ($q) => $q->whereDate('transaction_date', '<', $fromDate))
            ->selectRaw('COALESCE(SUM(CASE WHEN direction = ? THEN amount ELSE -amount END), 0) balance', [BankTransaction::DIRECTION_DEBIT])
            ->value('balance');
        $opening = round((float) $account->opening_balance + (float) $openingMovement, 2);

        $transactions = BankTransaction::query()->where('bank_account_id', $account->getKey())
            ->when($fromDate, fn ($q) => $q->whereDate('transaction_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('transaction_date', '<=', $toDate))
            ->orderBy('transaction_date')->orderBy('id')->get();

        $balance = $opening;
        $rows = $transactions->map(function (BankTransaction $tx) use (&$balance): array {
            $debit = $tx->direction === BankTransaction::DIRECTION_DEBIT ? (float) $tx->amount : 0.0;
            $credit = $tx->direction === BankTransaction::DIRECTION_CREDIT ? (float) $tx->amount : 0.0;
            $balance = round($balance + $debit - $credit, 2);
            return [
                'date' => $tx->transaction_date?->format('Y/m/d'),
                'document_number' => $tx->document_number,
                'reference_number' => $tx->reference_number,
                'description' => $tx->notes ?: $tx->type,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];
        });

        return [
            'account' => $account,
            'rows' => $rows,
            'opening_balance' => $opening,
            'total_debit' => round((float) $rows->sum('debit'), 2),
            'total_credit' => round((float) $rows->sum('credit'), 2),
            'closing_balance' => $balance,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }
}
