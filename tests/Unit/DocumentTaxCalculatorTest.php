<?php

namespace Tests\Unit;

use App\Enums\TaxType;
use App\Services\DocumentTaxCalculator;
use PHPUnit\Framework\TestCase;

class DocumentTaxCalculatorTest extends TestCase
{
    public function test_no_tax_produces_zero_tax(): void
    {
        $result = (new DocumentTaxCalculator)->calculate(10000, 0, TaxType::None);

        $this->assertSame(10000.0, $result['taxable_amount']);
        $this->assertSame(0.0, $result['tax_amount']);
        $this->assertSame(10000.0, $result['total']);
    }

    public function test_vat_is_calculated_after_discount(): void
    {
        $result = (new DocumentTaxCalculator)->calculate(10000, 1000, TaxType::Vat14);

        $this->assertSame(9000.0, $result['taxable_amount']);
        $this->assertSame(1260.0, $result['tax_amount']);
        $this->assertSame(10260.0, $result['total']);
    }

    public function test_totals_are_rounded_and_taxable_amount_cannot_be_negative(): void
    {
        $rounded = (new DocumentTaxCalculator)->calculate(10.03, 0, TaxType::Vat14);
        $zero = (new DocumentTaxCalculator)->calculate(100, 150, TaxType::Vat14);

        $this->assertSame(1.4, $rounded['tax_amount']);
        $this->assertSame(11.43, $rounded['total']);
        $this->assertSame(0.0, $zero['taxable_amount']);
        $this->assertSame(0.0, $zero['tax_amount']);
        $this->assertSame(0.0, $zero['total']);
    }
}
