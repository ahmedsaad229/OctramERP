<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class BankAccount extends BaseModel
{
    protected $fillable = [
        'bank_id', 'account_id', 'code', 'account_name', 'branch_name', 'account_number',
        'iban', 'currency', 'opening_balance', 'is_default', 'is_active', 'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (BankAccount $account): void {
            if (blank($account->code)) {
                $next = ((int) static::query()->max('id')) + 1;
                $account->code = 'BA'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }
        });

        static::saving(function (BankAccount $account): void {
            if ($account->is_default) {
                static::query()->when($account->exists, fn ($q) => $q->whereKeyNot($account->getKey()))
                    ->update(['is_default' => false]);
            }
        });

        static::deleting(function (BankAccount $account): void {
            if ($account->transactions()->exists()) {
                throw ValidationException::withMessages([
                    'bank_account' => 'لا يمكن حذف الحساب البنكي لوجود حركات مرتبطة به.',
                ]);
            }
        });
    }

    public function bank(): BelongsTo { return $this->belongsTo(Bank::class); }
    public function ledgerAccount(): BelongsTo { return $this->belongsTo(Account::class, 'account_id'); }
    public function transactions(): HasMany { return $this->hasMany(BankTransaction::class); }

    public function displayName(): string
    {
        return trim("{$this->bank?->name} - {$this->account_name} - {$this->account_number}", ' -');
    }
}
