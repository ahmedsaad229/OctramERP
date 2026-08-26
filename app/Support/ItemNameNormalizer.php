<?php

namespace App\Support;

final class ItemNameNormalizer
{
    public static function normalize(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        $value = str_replace("\xC2\xA0", ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = str_replace('ـ', '', $value);

        if (function_exists('mb_strtolower')) {
            $value = mb_strtolower($value, 'UTF-8');
        } else {
            $value = strtolower($value);
        }

        return trim($value);
    }
}
