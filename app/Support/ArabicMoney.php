<?php

namespace App\Support;

class ArabicMoney
{
    public static function format(float|int|string|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 2).' ج.م';
    }
}
