<?php

namespace App\Services;

use App\Enums\TaxType;

class DocumentTaxCalculator
{
    /**
     * @return array{taxable_amount: float, tax_amount: float, total: float}
     */
    public function calculate(float $subtotal, float $discountAmount, TaxType $taxType): array
    {
        $taxableAmount = round(max($subtotal - max($discountAmount, 0), 0), 2);
        $taxAmount = round($taxableAmount * $taxType->rate(), 2);

        return [
            'taxable_amount' => $taxableAmount,
            'tax_amount' => $taxAmount,
            'total' => round($taxableAmount + $taxAmount, 2),
        ];
    }
}
