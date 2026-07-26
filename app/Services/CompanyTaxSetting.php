<?php

namespace App\Services;

use App\Enums\TaxType;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CompanyTaxSetting
{
    public function resolve(): TaxType
    {
        try {
            if (
                ! Schema::hasTable('company_settings')
                || ! Schema::hasColumn('company_settings', 'default_tax_type')
            ) {
                return TaxType::Vat14;
            }

            $value = CompanySetting::query()->value('default_tax_type');

            return $value instanceof TaxType
                ? $value
                : (TaxType::tryFrom((string) $value) ?? TaxType::Vat14);
        } catch (Throwable) {
            return TaxType::Vat14;
        }
    }
}
