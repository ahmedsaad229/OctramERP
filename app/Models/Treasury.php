<?php

namespace App\Models;

use App\Services\DocumentNumberService;
use App\Support\Octram\Traits\HasCode;
use Illuminate\Validation\ValidationException;

class Treasury extends BaseModel
{
    use HasCode;

    protected static string $codePrefix = 'TRE';

    protected static string $documentType = DocumentNumberService::TREASURY;

    protected $fillable = [
        'code',
        'name',
        'opening_balance',
        'is_default',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Treasury $treasury): void {
            if (! $treasury->is_default) {
                return;
            }

            $hasAnotherDefault = static::query()
                ->where('is_default', true)
                ->when(
                    $treasury->exists,
                    fn ($query) => $query->whereKeyNot($treasury->getKey()),
                )
                ->exists();

            if ($hasAnotherDefault) {
                throw ValidationException::withMessages([
                    'is_default' => 'يمكن تحديد خزينة افتراضية واحدة فقط.',
                ]);
            }
        });
    }
}
