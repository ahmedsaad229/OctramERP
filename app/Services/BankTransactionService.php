<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BankTransactionService
{
    public function replaceForSource(
        BankAccount $account, Model $source, mixed $date, string $type, float $amount,
        string $direction, ?string $documentNumber = null, ?string $referenceNumber = null,
        ?string $notes = null,
    ): BankTransaction {
        return DB::transaction(function () use ($account, $source, $date, $type, $amount, $direction, $documentNumber, $referenceNumber, $notes): BankTransaction {
            BankTransaction::query()
                ->where('source_type', $source->getMorphClass())
                ->where('source_id', $source->getKey())
                ->where('bank_account_id', $account->getKey())
                ->where('type', $type)
                ->delete();

            return BankTransaction::query()->create([
                'bank_account_id' => $account->getKey(),
                'transaction_date' => $date,
                'type' => $type,
                'direction' => $direction,
                'amount' => round($amount, 2),
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'document_number' => $documentNumber,
                'reference_number' => $referenceNumber,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);
        });
    }

    public function deleteForSource(Model $source): void
    {
        BankTransaction::query()->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())->delete();
    }

    public function balance(BankAccount|int $account): float
    {
        $account = $account instanceof BankAccount ? $account : BankAccount::query()->findOrFail($account);
        $movement = (float) BankTransaction::query()->where('bank_account_id', $account->getKey())
            ->selectRaw('COALESCE(SUM(CASE WHEN direction = ? THEN amount ELSE -amount END), 0) AS balance', [BankTransaction::DIRECTION_DEBIT])
            ->value('balance');
        return round((float) $account->opening_balance + $movement, 2);
    }
}
