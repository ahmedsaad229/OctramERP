<?php

namespace App\Models;

use App\Enums\TaxType;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends BaseModel
{
    protected $fillable = [
        'company_name',
        'default_tax_type',
        'logo_path',
        'default_sales_quotation_terms',
        'address',
        'phone',
        'mobile',
        'email',
        'website',
        'commercial_registry',
        'tax_number',
    ];

    protected $casts = [
        'default_tax_type' => TaxType::class,
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'company_name' => self::configuredCommercialName(),
            'default_tax_type' => TaxType::Vat14->value,
        ]);
    }

    public function commercialName(): string
    {
        $stored = trim((string) $this->company_name);

        if (filled($stored) && strcasecmp($stored, 'Laravel') !== 0) {
            return $stored;
        }

        return self::configuredCommercialName();
    }

    public function logoUrl(): ?string
    {
        if (
            blank($this->logo_path)
            || ! Storage::disk('public')->exists($this->logo_path)
        ) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    private static function configuredCommercialName(): string
    {
        $configured = trim((string) config('company.name', ''));

        return filled($configured)
            && strcasecmp($configured, 'Laravel') !== 0
                ? $configured
                : 'أوكترام للمقاولات والتوريدات';
    }
}
