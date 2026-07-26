<?php

namespace Tests\Unit;

use App\Support\QuantityFormatter;
use PHPUnit\Framework\TestCase;

class QuantityFormatterTest extends TestCase
{
    public function test_quantities_use_western_digits_and_trim_unnecessary_zeroes(): void
    {
        $this->assertSame('5', QuantityFormatter::formatForDisplay('5.00'));
        $this->assertSame('100', QuantityFormatter::formatForDisplay(100));
        $this->assertSame('1.5', QuantityFormatter::formatForDisplay(1.5));
        $this->assertSame('25.75', QuantityFormatter::formatForDisplay('25.75'));
        $this->assertSame('5', QuantityFormatter::formatForDisplay('٥٫٠٠'));
        $this->assertSame('100', QuantityFormatter::normalizeForInput('١٠٠'));
        $this->assertSame('-1', QuantityFormatter::normalizeForInput('-1'));
        $this->assertSame('0', QuantityFormatter::normalizeForInput('0'));
        $this->assertNull(QuantityFormatter::normalizeForInput('abc'));
    }

    public function test_input_attributes_are_scoped_and_keep_ltr_inside_rtl_pages(): void
    {
        $attributes = QuantityFormatter::inputAttributes();

        $this->assertSame('ltr', $attributes['dir']);
        $this->assertSame('en', $attributes['lang']);
        $this->assertSame('decimal', $attributes['inputmode']);
        $this->assertStringContainsString('unicode-bidi: plaintext', $attributes['style']);
        $this->assertStringContainsString('octram-quantity-input', $attributes['class']);
        $this->assertSame('octram-quantity-input', $attributes['class']);
        $this->assertStringNotContainsString('spin-button', $attributes['class']);
    }

    public function test_read_only_numeric_values_use_the_scoped_centered_display_style(): void
    {
        $attributes = QuantityFormatter::displayAttributes();

        $this->assertSame('octram-quantity-display', $attributes['class']);
        $this->assertStringContainsString('text-align: center', $attributes['style']);
        $this->assertStringContainsString('unicode-bidi: plaintext', $attributes['style']);
    }
}
