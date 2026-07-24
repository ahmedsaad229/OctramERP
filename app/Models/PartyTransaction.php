<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class PartyTransaction extends BaseModel
{
    public const TYPE_PURCHASE_INVOICE = 'purchase_invoice';

    protected $fillable = [
        'party_type',
        'party_id',
        'transaction_type',
        'source_type',
        'source_id',
        'reference_no',
        'transaction_date',
        'debit',
        'credit',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function party(): MorphTo
    {
        return $this->morphTo();
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
