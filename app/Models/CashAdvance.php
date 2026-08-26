<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAdvance extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_SETTLED = 'settled';

    protected $fillable = [
        'document_number',
        'advance_date',
        'recipient_name',
        'purpose',
        'amount',
        'due_date',
        'total_spent',
        'total_returned',
        'remaining_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'advance_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'total_returned' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (CashAdvance $advance): void {
            if (blank($advance->document_number)) {
                $advance->updateQuietly([
                    'document_number' => 'ADV-' . str_pad(
                        (string) $advance->id,
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),
                ]);
            }

            $advance->recalculate();
        });
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(CashAdvanceSettlement::class);
    }

    public function recalculate(): void
    {
        $spent = round(
            (float) $this->settlements()
                ->whereIn('type', CashAdvanceSettlement::expenseTypes())
                ->sum('amount'),
            2
        );

        $returned = round(
            (float) $this->settlements()
                ->where('type', CashAdvanceSettlement::TYPE_RETURN)
                ->sum('amount'),
            2
        );

        $amount = round((float) $this->amount, 2);

        $remaining = round(
            max(0, $amount - $spent - $returned),
            2
        );

        $status = match (true) {
            $remaining <= 0.009 => self::STATUS_SETTLED,
            ($spent + $returned) > 0 => self::STATUS_PARTIAL,
            default => self::STATUS_OPEN,
        };

        $this->updateQuietly([
            'total_spent' => $spent,
            'total_returned' => $returned,
            'remaining_amount' => $remaining,
            'status' => $status,
        ]);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'مفتوحة',
            self::STATUS_PARTIAL => 'مسواة جزئيًا',
            self::STATUS_SETTLED => 'مسواة بالكامل',
        ];
    }
}
