<?php

namespace App\Services;

use App\Models\PartyTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PartyTransactionService
{
    public function replaceDocumentTransaction(
        Model $party,
        string $transactionType,
        Model $source,
        mixed $transactionDate,
        float $debit,
        float $credit,
        ?string $referenceNo = null,
        ?string $notes = null,
    ): PartyTransaction {
        return DB::transaction(function () use (
            $party,
            $transactionType,
            $source,
            $transactionDate,
            $debit,
            $credit,
            $referenceNo,
            $notes,
        ): PartyTransaction {
            PartyTransaction::query()
                ->where('source_type', $source->getMorphClass())
                ->where('source_id', $source->getKey())
                ->delete();

            return PartyTransaction::create([
                'party_type' => $party->getMorphClass(),
                'party_id' => $party->getKey(),
                'transaction_type' => $transactionType,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'reference_no' => $referenceNo,
                'transaction_date' => $transactionDate,
                'debit' => $debit,
                'credit' => $credit,
                'notes' => $notes,
            ]);
        });
    }
}
