<?php

namespace App\Services;

use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TreasuryTransactionService
{
    public function replaceForSource(
        Treasury $treasury,
        Model $source,
        mixed $transactionDate,
        string $type,
        float $amount,
        string $direction,
        ?string $documentNumber = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): TreasuryTransaction {
        $this->validateTransaction($amount, $direction);

        return DB::transaction(function () use (
            $treasury,
            $source,
            $transactionDate,
            $type,
            $amount,
            $direction,
            $documentNumber,
            $notes,
            $createdBy,
        ): TreasuryTransaction {
            $this->deleteForSource($source);

            return TreasuryTransaction::create([
                'treasury_id' => $treasury->getKey(),
                'transaction_date' => $transactionDate,
                'type' => $type,
                'amount' => $amount,
                'direction' => $direction,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'document_number' => $documentNumber,
                'notes' => $notes,
                'created_by' => $createdBy ?? auth()->id(),
            ]);
        });
    }

    public function deleteForSource(Model $source): void
    {
        TreasuryTransaction::query()
            ->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->delete();
    }

    public function getBalance(Treasury|int $treasury): float
    {
        $treasury = $treasury instanceof Treasury
            ? $treasury
            : Treasury::query()->findOrFail($treasury);

        $transactionBalance = TreasuryTransaction::query()
            ->where('treasury_id', $treasury->getKey())
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN direction = ? THEN amount ELSE -amount END), 0) AS balance',
                [TreasuryTransaction::DIRECTION_DEBIT],
            )
            ->value('balance');

        return (float) $treasury->opening_balance + (float) $transactionBalance;
    }

    private function validateTransaction(float $amount, string $direction): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'يجب أن يكون المبلغ أكبر من صفر.',
            ]);
        }

        if (! in_array($direction, [
            TreasuryTransaction::DIRECTION_DEBIT,
            TreasuryTransaction::DIRECTION_CREDIT,
        ], true)) {
            throw ValidationException::withMessages([
                'direction' => 'اتجاه حركة الخزينة غير صحيح.',
            ]);
        }
    }
}
