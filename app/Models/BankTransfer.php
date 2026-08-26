<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransfer extends BaseModel
{
    protected $fillable = [
        'document_number', 'transfer_date', 'from_bank_account_id', 'to_bank_account_id',
        'amount', 'fees', 'reference_number', 'notes', 'created_by',
    ];
    protected $casts = ['transfer_date' => 'date', 'amount' => 'decimal:2', 'fees' => 'decimal:2'];

    public function fromAccount(): BelongsTo { return $this->belongsTo(BankAccount::class, 'from_bank_account_id'); }
    public function toAccount(): BelongsTo { return $this->belongsTo(BankAccount::class, 'to_bank_account_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
