<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class JournalEntry extends BaseModel
{
    public const TYPE_AUTOMATIC = 'automatic';
    public const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'entry_date',
        'entry_type',
        'source_type',
        'source_id',
        'document_number',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (JournalEntry $entry): void {
            $entry->entry_type ??= self::TYPE_MANUAL;

            if ($entry->entry_type === self::TYPE_MANUAL) {
                $entry->source_type = 'manual';
                $entry->source_id = null;

                if (blank($entry->document_number)) {
                    $next = ((int) static::query()->max('id')) + 1;
                    $entry->document_number = 'JV'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
                }
            }
        });

        static::deleting(function (JournalEntry $entry): void {
            if ($entry->entry_type === self::TYPE_AUTOMATIC) {
                throw ValidationException::withMessages([
                    'journal_entry' => 'لا يمكن حذف القيد الأوتوماتيكي مباشرة. تعامل مع المستند الأصلي.',
                ]);
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isManual(): bool
    {
        return $this->entry_type === self::TYPE_MANUAL;
    }

    public function isAutomatic(): bool
    {
        return $this->entry_type === self::TYPE_AUTOMATIC;
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_AUTOMATIC => 'أوتوماتيك',
            self::TYPE_MANUAL => 'يدوي',
        ];
    }

    public function totalDebit(): float
    {
        return round((float) $this->lines->sum('debit'), 2);
    }

    public function totalCredit(): float
    {
        return round((float) $this->lines->sum('credit'), 2);
    }
}
