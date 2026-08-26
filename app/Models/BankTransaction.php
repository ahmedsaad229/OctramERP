<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankTransaction extends BaseModel
{
    public const TYPE_OPENING = 'opening';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_FEES = 'fees';
    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_PAYMENT = 'payment';

    public const DIRECTION_DEBIT = 'debit';
    public const DIRECTION_CREDIT = 'credit';

    protected $fillable = [
        'bank_account_id', 'transaction_date', 'type', 'direction', 'amount',
        'source_type', 'source_id', 'document_number', 'reference_number', 'notes', 'created_by',
    ];
    protected $casts = ['transaction_date' => 'date', 'amount' => 'decimal:2'];

    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function source(): MorphTo { return $this->morphTo(); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
