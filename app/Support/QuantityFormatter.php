<?php

namespace App\Support;

class QuantityFormatter
{
    public static function normalizeForInput(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = strtr((string) $value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٫' => '.', '٬' => '', ',' => '',
        ]);

        if (! is_numeric($normalized)) {
            return null;
        }

        return self::trim(number_format((float) $normalized, 2, '.', ''));
    }

    public static function formatForDisplay(mixed $value): string
    {
        return self::normalizeForInput($value) ?? '0';
    }

    /** @return array<string, string> */
    public static function inputAttributes(): array
    {
        return [
            'dir' => 'ltr',
            'lang' => 'en',
            'inputmode' => 'decimal',
            'autocomplete' => 'off',
            'style' => 'text-align: center; unicode-bidi: plaintext;',
            'class' => 'octram-quantity-input',
        ];
    }

    /** @return array<string, string> */
    public static function displayAttributes(): array
    {
        return [
            'dir' => 'ltr',
            'lang' => 'en',
            'style' => 'display: flex; min-height: 2.25rem; align-items: center; justify-content: center; text-align: center; unicode-bidi: plaintext;',
            'class' => 'octram-quantity-display',
        ];
    }

    private static function trim(string $value): string
    {
        return rtrim(rtrim($value, '0'), '.');
    }
}
