<?php

namespace App\Services;

use App\Models\Account;

class AccountingAccountResolver
{
    public const CASH = '1110';
    public const CUSTOMERS = '1210';
    public const INPUT_VAT = '1410';
    public const SUPPLIERS = '2110';
    public const OUTPUT_VAT = '2210';
    public const SERVICE_TAX_DISCOUNT = '1420';
    public const ONE_PERCENT_DISCOUNT = '1430';
    public const SALES = '4110';
    public const PURCHASES = '5110';
    public const GENERAL_EXPENSES = '6110';
    public const OTHER_INCOME = '4210';

    /** @return array<string, Account> */
    public function ensureAccounts(): array
    {
        $definitions = [
            self::CASH => ['النقدية والخزائن', Account::TYPE_ASSET, Account::BALANCE_DEBIT],
            self::CUSTOMERS => ['العملاء', Account::TYPE_ASSET, Account::BALANCE_DEBIT],
            self::INPUT_VAT => ['ضريبة القيمة المضافة - مدخلات', Account::TYPE_ASSET, Account::BALANCE_DEBIT],
            self::SUPPLIERS => ['الموردون', Account::TYPE_LIABILITY, Account::BALANCE_CREDIT],
            self::OUTPUT_VAT => ['ضريبة القيمة المضافة - مخرجات', Account::TYPE_LIABILITY, Account::BALANCE_CREDIT],
            self::SERVICE_TAX_DISCOUNT => ['خصم ضريبة خدمات 3%', Account::TYPE_ASSET, Account::BALANCE_DEBIT],
            self::ONE_PERCENT_DISCOUNT => ['خصم وإضافة 1%', Account::TYPE_ASSET, Account::BALANCE_DEBIT],
            self::SALES => ['إيرادات المبيعات', Account::TYPE_REVENUE, Account::BALANCE_CREDIT],
            self::PURCHASES => ['المشتريات وتكلفة التوريدات', Account::TYPE_COST, Account::BALANCE_DEBIT],
            self::GENERAL_EXPENSES => ['مصروفات عامة وتشغيلية', Account::TYPE_EXPENSE, Account::BALANCE_DEBIT],
            self::OTHER_INCOME => ['إيرادات أخرى', Account::TYPE_OTHER_REVENUE, Account::BALANCE_CREDIT],
        ];

        $accounts = [];
        foreach ($definitions as $code => [$name, $type, $balance]) {
            $accounts[$code] = Account::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'account_type' => $type,
                    'normal_balance' => $balance,
                    'is_group' => false,
                    'allow_posting' => true,
                    'active' => true,
                    'level' => 1,
                    'sort_order' => (int) $code,
                ],
            );
        }

        return $accounts;
    }
}
