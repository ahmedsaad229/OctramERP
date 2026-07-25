<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TreasuryTransaction extends BaseModel
{
    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_OPENING = 'opening';

    public const DIRECTION_DEBIT = 'debit';
    public const DIRECTION_CREDIT = 'credit';

    protected $fillable = [
        'treasury_id',
        'transaction_date',
        'type',
        'amount',
        'direction',
        'source_type',
        'source_id',
        'document_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
