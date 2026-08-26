<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAdvanceSettlement extends Model
{
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_TRANSPORT = 'transport';
    public const TYPE_GRATUITY = 'gratuity';
    public const TYPE_OTHER_EXPENSE = 'other_expense';
    public const TYPE_RETURN = 'return';

    protected $fillable = [
        'cash_advance_id',
        'settlement_date',
        'item_name',
        'type',
        'description',
        'document_number',
        'amount',
        'notes',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function advance(): BelongsTo
    {
        return $this->belongsTo(CashAdvance::class, 'cash_advance_id');
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_EXPENSE => 'مصروف',
            self::TYPE_PURCHASE => 'مشتريات',
            self::TYPE_TRANSPORT => 'انتقالات',
            self::TYPE_GRATUITY => 'إكراميات',
            self::TYPE_OTHER_EXPENSE => 'مصروفات أخرى',
            self::TYPE_RETURN => 'مبلغ مرتجع',
        ];
    }

    public static function expenseTypes(): array
    {
        return [
            self::TYPE_EXPENSE,
            self::TYPE_PURCHASE,
            self::TYPE_TRANSPORT,
            self::TYPE_GRATUITY,
            self::TYPE_OTHER_EXPENSE,
        ];
    }
}
