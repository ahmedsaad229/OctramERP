<?php

namespace App\Support;

final class PermissionRegistry
{
    /** @return array<string, string> */
    public static function modules(): array
    {
        return [
            'users' => 'المستخدمون',
            'company_settings' => 'إعدادات الشركة',
            'roles' => 'الأدوار والصلاحيات',

            'customers' => 'العملاء',
            'suppliers' => 'الموردون',

            'items' => 'الأصناف',
            'categories' => 'التصنيفات',
            'units' => 'الوحدات',
            'warehouses' => 'المخازن',
            'treasuries' => 'الخزن والحسابات النقدية',

            'banks' => 'البنوك',
            'bank_accounts' => 'الحسابات البنكية',
            'bank_transfers' => 'التحويلات البنكية',
            'bank_statements' => 'كشف حساب البنك',
            'bank_checks' => 'الشيكات',

            'accounts' => 'الحسابات',
            'journal_entries' => 'القيود اليومية',

            // تقارير الحسابات العامة التي تستخدم صلاحيات مستقلة.
            'general_ledger' => 'الأستاذ العام',
            'income_statement' => 'قائمة الدخل',
            'balance_sheet' => 'الميزانية العمومية',
            'cash_flow_statement' => 'قائمة التدفقات النقدية',
            'trial_balance' => 'ميزان المراجعة',
            'purchase_request_monitoring' => 'متابعة طلبات الشراء',
            'customer_purchase_order_monitoring' => 'متابعة أوامر توريد العملاء',
            'sales_reports' => 'تقارير المبيعات',
            'low_stock_report' => 'تقرير الحد الأدنى للمخزون',
            'inventory_stock_balance_report' => 'تقرير أرصدة المخزون',
            'inventory_movement_report' => 'تقرير حركة المخزون',
            'item_movement' => 'حركة صنف',
            'purchase_item_sales_tracking' => 'تتبع أصناف المشتريات',


            'purchase_requests' => 'طلبات الشراء',
            'supplier_purchase_orders' => 'أوامر شراء الموردين',
            'purchase_invoices' => 'فواتير الشراء',
            'supplier_payment_vouchers' => 'سندات صرف الموردين',
            'cash_payment_vouchers' => 'سندات الصرف النقدي',

            'sales_quotations' => 'عروض الأسعار',
            'customer_purchase_orders' => 'أوامر توريد العملاء',
            'sales_invoices' => 'فواتير البيع',
            'receipt_vouchers' => 'سندات قبض العملاء',
            'cash_receipt_vouchers' => 'سندات القبض النقدي',
            'customer_follow_ups' => 'متابعة العملاء',

            'opening_stock_vouchers' => 'أرصدة أول المدة',
            'goods_receipt_vouchers' => 'أذون الإضافة',
            'goods_issue_vouchers' => 'أذون الصرف',
            'stock_balances' => 'أرصدة المخزون',
            'due_obligations' => 'الاستحقاقات',
            'octram_entries' => 'أوكترام',
            'cash_advances' => 'العهد النقدية',
        ];
    }

    /** @return array<string, string> */
    public static function actions(): array
    {
        return [
            'view' => 'عرض',
            'create' => 'إضافة',
            'edit' => 'تعديل',
            'delete' => 'حذف',
            'print' => 'طباعة',
            'export' => 'تصدير',
        ];
    }

    /** @return array<string, string> */
    public static function permissionOptions(): array
    {
        $options = [];

        $reportModules = [
            'general_ledger',
            'trial_balance',
            'income_statement',
            'balance_sheet',
            'cash_flow_statement',
            'purchase_request_monitoring',
            'customer_purchase_order_monitoring',
            'sales_reports',
            'low_stock_report',
            'inventory_stock_balance_report',
            'inventory_movement_report',
            'item_movement',
            'purchase_item_sales_tracking',
            'stock_balances',
            'due_obligations',
            'bank_statements',
        ];

        foreach (self::modules() as $module => $moduleLabel) {
            foreach (self::actions() as $action => $actionLabel) {
                if (in_array($module, $reportModules, true) && ! in_array($action, ['view', 'print', 'export'], true)) {
                    continue;
                }

                $options["{$module}.{$action}"] = "{$moduleLabel} — {$actionLabel}";
            }
        }

        // لوحة المعلومات: صلاحية عرض مستقلة فقط.
        $options['dashboard.view'] = 'لوحة المعلومات — عرض';
        // الأستاذ المساعد يظهر في الأدوار كبندين مستقلين فقط.
        $options['customer_statements.view'] = 'الأستاذ المساعد - العملاء';
        $options['supplier_statements.view'] = 'الأستاذ المساعد - الموردين';

        // صلاحية عامة سبق استخدامها لمنع/السماح بحذف المستندات.
        $options['documents.delete'] = 'المستندات — حذف';

        return $options;
    }
}