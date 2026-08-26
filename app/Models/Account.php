<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Account extends BaseModel
{
    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_REVENUE = 'revenue';
    public const TYPE_COST = 'cost';
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_OTHER_REVENUE = 'other_revenue';
    public const TYPE_OTHER_EXPENSE = 'other_expense';
    public const TYPE_CONTROL = 'control';

    public const BALANCE_DEBIT = 'debit';
    public const BALANCE_CREDIT = 'credit';

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'name_en',
        'account_type',
        'normal_balance',
        'is_group',
        'allow_posting',
        'requires_cost_center',
        'active',
        'level',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'allow_posting' => 'boolean',
        'requires_cost_center' => 'boolean',
        'active' => 'boolean',
        'level' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Account $account): void {
            if ($account->parent_id) {
                $parent = static::query()->find($account->parent_id);

                if (! $parent) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'الحساب الأب غير موجود.',
                    ]);
                }

                if ($account->exists && $parent->is($account)) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'لا يمكن اختيار الحساب نفسه كحساب أب.',
                    ]);
                }

                $account->level = $parent->level + 1;
            } else {
                $account->level = 1;
            }

            if ($account->is_group) {
                $account->allow_posting = false;
            }
        });

        static::deleting(function (Account $account): void {
            if ($account->children()->exists()) {
                throw ValidationException::withMessages([
                    'account' => 'لا يمكن حذف الحساب لأنه يحتوي على حسابات فرعية.',
                ]);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('code');
    }

    public function scopePosting(Builder $query): Builder
    {
        return $query
            ->where('allow_posting', true)
            ->where('active', true);
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_ASSET => 'أصول',
            self::TYPE_LIABILITY => 'التزامات',
            self::TYPE_EQUITY => 'حقوق ملكية',
            self::TYPE_REVENUE => 'إيرادات',
            self::TYPE_COST => 'تكلفة الإيرادات والمشروعات',
            self::TYPE_EXPENSE => 'مصروفات',
            self::TYPE_OTHER_REVENUE => 'إيرادات أخرى',
            self::TYPE_OTHER_EXPENSE => 'مصروفات أخرى',
            self::TYPE_CONTROL => 'حسابات نظامية ورقابية',
        ];
    }

    public static function balanceOptions(): array
    {
        return [
            self::BALANCE_DEBIT => 'مدين',
            self::BALANCE_CREDIT => 'دائن',
        ];
    }

    public function displayName(): string
    {
        return "{$this->code} - {$this->name}";
    }
}
