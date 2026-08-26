<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends BaseModel
{
    protected $fillable = ['code', 'name', 'name_en', 'swift_code', 'website', 'notes', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Bank $bank): void {
            if (blank($bank->code)) {
                $next = ((int) static::query()->max('id')) + 1;
                $bank->code = 'BK'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }
}
