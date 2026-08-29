<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Role;
use App\Support\PermissionRegistry;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات المستخدم')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('الاسم')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->required()
                                    ->email()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),

                                TextInput::make('mobile')
                                    ->label('رقم الموبايل')
                                    ->tel()
                                    ->maxLength(50),

                                TextInput::make('job_title')
                                    ->label('المسمى الوظيفي')
                                    ->maxLength(150),

                                TextInput::make('password')
                                    ->label('كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->minLength(8)
                                    ->same('password_confirmation')
                                    ->dehydrated(fn (?string $state): bool => filled($state)),

                                TextInput::make('password_confirmation')
                                    ->label('تأكيد كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->minLength(8)
                                    ->dehydrated(false),

                                Select::make('role_id')
                                    ->label('الدور الوظيفي - قالب اختياري')
                                    ->options(fn (): array => Role::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->helperText('الدور الوظيفي للتصنيف فقط، والصلاحيات يتم تحديدها يدويًا لكل مستخدم.'),

                                Toggle::make('is_active')
                                    ->label('نشط')
                                    ->default(true),

                                Toggle::make('is_admin')
                                    ->label('مدير نظام')
                                    ->default(false)
                                    ->live()
                                    ->helperText('مدير النظام لديه كل الصلاحيات تلقائيًا.'),
                            ]),
                    ]),

                self::permissionSection(
                    'صلاحيات المبيعات',
                    'sales_permissions',
                    self::salesModules()
                ),

                self::permissionSection(
                    'صلاحيات المشتريات',
                    'purchase_permissions',
                    self::purchaseModules()
                ),

                self::permissionSection(
                    'صلاحيات المخزون',
                    'inventory_permissions',
                    self::inventoryModules()
                ),

                self::permissionSection(
                    'صلاحيات الحسابات العامة',
                    'accounting_permissions',
                    self::accountingModules()
                ),

                self::permissionSection(
                    'صلاحيات التقارير والمتابعة',
                    'report_permissions',
                    self::reportModules()
                ),

                self::permissionSection(
                    'صلاحيات النظام والإعدادات',
                    'system_permissions',
                    self::systemModules()
                ),
            ]);
    }

    private static function permissionSection(
        string $title,
        string $field,
        array $modules
    ): Section {
        return Section::make($title)
            ->visible(fn ($get): bool => ! (bool) $get('is_admin'))
            ->description('فتح الشاشة يشمل العرض والطباعة والتصدير تلقائيًا.')
            ->schema([
                CheckboxList::make($field)
                    ->label('')
                    ->options(self::optionsForModules($modules))
                    ->columns(2)
                    ->bulkToggleable()
                    ->searchable()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (CheckboxList $component, $record) use ($modules): void {
                        // New user: start with absolutely no permissions.
                        if (! $record) {
                            $component->state([]);
                            return;
                        }

                        // Existing user: show only the permissions already saved for that user.
                        $permissions = $record->permissions ?? [];

                        $component->state(
                            self::filterPermissionsForModules(
                                self::uiPermissions($permissions),
                                $modules
                            )
                        );
                    })
                    ->afterStateUpdated(function ($state, $get, $set): void {
                        self::syncMasterPermissions($get, $set);
                    }),
            ]);
    }

    private static function syncMasterPermissions($get, $set): void
    {
        $permissions = [];

        foreach (array_keys(self::sectionFields()) as $field) {
            $permissions = array_merge(
                $permissions,
                $get($field) ?? []
            );
        }

        $set(
            'permissions',
            self::normalizePermissions(
                array_values(array_unique($permissions))
            )
        );
    }

    private static function sectionFields(): array
    {
        return [
            'sales_permissions' => self::salesModules(),
            'purchase_permissions' => self::purchaseModules(),
            'inventory_permissions' => self::inventoryModules(),
            'accounting_permissions' => self::accountingModules(),
            'report_permissions' => self::reportModules(),
            'system_permissions' => self::systemModules(),
        ];
    }

    private static function salesModules(): array
    {
        return [
            'customers',
            'sales_quotations',
            'customer_purchase_orders',
            'sales_invoices',
            'receipt_vouchers',
            'cash_receipt_vouchers',
            'customer_follow_ups',
            'customer_statements',
        ];
    }

    private static function purchaseModules(): array
    {
        return [
            'suppliers',
            'purchase_requests',
            'supplier_purchase_orders',
            'purchase_invoices',
            'supplier_payment_vouchers',
            'cash_payment_vouchers',
            'supplier_statements',
        ];
    }

    private static function inventoryModules(): array
    {
        return [
            'items',
            'categories',
            'units',
            'warehouses',
            'opening_stock_vouchers',
            'goods_receipt_vouchers',
            'goods_issue_vouchers',
            'stock_balances',
        ];
    }

    private static function accountingModules(): array
    {
        return [
            'treasuries',
            'banks',
            'bank_accounts',
            'bank_transfers',
            'bank_statements',
            'bank_checks',
            'accounts',
            'journal_entries',
            'cash_advances',
            'due_obligations',
            'octram_entries',
        ];
    }

    private static function reportModules(): array
    {
        return [
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
        ];
    }

    private static function systemModules(): array
    {
        return [
            'dashboard',
            'users',
            'company_settings',
        ];
    }

    private static function optionsForModules(array $modules): array
    {
        $allModules = PermissionRegistry::modules();
        $options = [];

        foreach ($modules as $module) {
            if ($module === 'dashboard') {
                $options['dashboard.view'] = 'لوحة المعلومات — فتح الشاشة';
                continue;
            }

            if ($module === 'customer_statements') {
                $options['customer_statements.view'] = 'الأستاذ المساعد - العملاء — فتح الشاشة';
                continue;
            }

            if ($module === 'supplier_statements') {
                $options['supplier_statements.view'] = 'الأستاذ المساعد - الموردين — فتح الشاشة';
                continue;
            }

            if (! isset($allModules[$module])) {
                continue;
            }

            $label = $allModules[$module];

            $options["{$module}.view"] = "{$label} — فتح الشاشة";

            if (self::supportsWriteActions($module)) {
                $options["{$module}.create"] = "{$label} — إضافة";
                $options["{$module}.edit"] = "{$label} — تعديل";
                $options["{$module}.delete"] = "{$label} — حذف";
            }
        }

        return $options;
    }

    private static function supportsWriteActions(string $module): bool
    {
        return ! in_array($module, [
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
        ], true);
    }

    private static function filterPermissionsForModules(
        array $permissions,
        array $modules
    ): array {
        return array_values(array_filter(
            $permissions,
            function (string $permission) use ($modules): bool {
                foreach ($modules as $module) {
                    if (str_starts_with($permission, $module.'.')) {
                        return true;
                    }
                }

                return false;
            }
        ));
    }

    private static function uiPermissions(array $permissions): array
    {
        return array_values(array_filter(
            $permissions,
            fn (string $permission): bool =>
                ! str_ends_with($permission, '.print')
                && ! str_ends_with($permission, '.export')
                && $permission !== 'documents.delete'
        ));
    }

    private static function normalizePermissions(array $permissions): array
    {
        $permissions = array_values(array_unique($permissions));

        foreach ($permissions as $permission) {
            if (! str_ends_with($permission, '.view')) {
                continue;
            }

            $module = substr($permission, 0, -5);

            if ($module === 'dashboard') {
                continue;
            }

            $permissions[] = "{$module}.print";
            $permissions[] = "{$module}.export";
        }

        return array_values(array_unique($permissions));
    }
}
