<?php

namespace App\Enums;

enum TaxType: string
{
    case None = 'none';
    case Vat14 = 'vat_14';

    public function label(): string
    {
        return match ($this) {
            self::None => 'بدون ضريبة',
            self::Vat14 => 'ضريبة قيمة مضافة 14%',
        };
    }

    public function rate(): float
    {
        return match ($this) {
            self::None => 0.0,
            self::Vat14 => 0.14,
        };
    }

    public function percentage(): string
    {
        return number_format($this->rate() * 100, 0).'%';
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_column(
            array_map(fn (self $type): array => [$type->value, $type->label()], self::cases()),
            1,
            0,
        );
    }
}
