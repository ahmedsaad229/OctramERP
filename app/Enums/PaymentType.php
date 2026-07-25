<?php

namespace App\Enums;

enum PaymentType: string
{
    case Cash = 'cash';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'كاش',
            self::Credit => 'آجل',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Cash->value => self::Cash->label(),
            self::Credit->value => self::Credit->label(),
        ];
    }
}
