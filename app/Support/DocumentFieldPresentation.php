<?php

namespace App\Support;

class DocumentFieldPresentation
{
    /** @return array<string, string> */
    public static function itemCode(): array
    {
        return self::box('octram-item-code-box', true);
    }

    /** @return array<string, string> */
    public static function unit(): array
    {
        return self::box('octram-unit-box');
    }

    /** @return array<string, string> */
    public static function money(): array
    {
        return self::box('octram-summary-box octram-money-box', true);
    }

    /** @return array<string, string> */
    public static function stock(): array
    {
        return self::box('octram-summary-box octram-stock-box', true);
    }

    /** @return array<string, string> */
    public static function value(bool $ltr = false): array
    {
        return self::box('octram-summary-box', $ltr);
    }

    /** @return array<string, string> */
    public static function wrapper(): array
    {
        return ['class' => 'octram-centered-entry'];
    }

    /** @return array<string, string> */
    private static function box(string $classes, bool $ltr = false): array
    {
        $attributes = [
            'class' => "octram-readonly-box {$classes}",
        ];

        if ($ltr) {
            $attributes['dir'] = 'ltr';
            $attributes['lang'] = 'en';
            $attributes['style'] = 'direction: ltr; unicode-bidi: isolate;';
        }

        return $attributes;
    }
}
