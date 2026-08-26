<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Support\PermissionRegistry;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $all = PermissionRegistry::permissionOptions();

        $group = function (array $modules) use ($all): array {
            return collect($all)
                ->filter(function (string $label, string $permission) use ($modules): bool {
                    foreach ($modules as $module) {
                        if (
                            $permission === $module ||
                            str_starts_with($permission, $module . '.')
                        ) {
                            return true;
                        }
                    }

                    return false;
                })
                ->all();
        };

        return $schema->components([

            Section::make('بيانات الدور')
                ->schema([
                    TextInput::make('name')
                        ->label('اسم الدور')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100),

                    Textarea::make('description')
                        ->label('الوصف')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->columns(2),

            Section::make('المبيعات')
                ->description('صلاحيات دورة المبيعات والعملاء.')
                ->schema([
                    CheckboxList::make('sales_permissions')
                        ->label('')
                        ->options($group([
                            'customers',
                            'sales_quotations',

                            // أوامر توريد العملاء
                            'customer_purchase_orders',

                            'sales_invoices',
                            'receipt_vouchers',
                            'cash_receipt_vouchers',
                            'customer_follow_ups',
                        ]))
                        ->columns(3)
                        ->bulkToggleable()
                        ->searchable()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (CheckboxList $component, $record): void {
                            $allowed = array_keys($component->getOptions());

                            $component->state(
                                array_values(
                                    array_intersect(
                                        $record?->permissions ?? [],
                                        $allowed
                                    )
                                )
                            );
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get): void {
                            self::mergePermissions($set, $get);
                        }),
                ]),

            Section::make('المشتريات')
                ->description('صلاحيات دورة المشتريات والموردين.')
                ->schema([
                    CheckboxList::make('purchase_permissions')
                        ->label('')
                        ->options($group([
                            'suppliers',
                            'purchase_requests',

                            // أوامر شراء الموردين
                            'supplier_purchase_orders',

                            'purchase_invoices',
                            'supplier_payment_vouchers',
                            'cash_payment_vouchers',
                        ]))
                        ->columns(3)
                        ->bulkToggleable()
                        ->searchable()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (CheckboxList $component, $record): void {
                            $allowed = array_keys($component->getOptions());

                            $component->state(
                                array_values(
                                    array_intersect(
                                        $record?->permissions ?? [],
                                        $allowed
                                    )
                                )
                            );
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get): void {
                            self::mergePermissions($set, $get);
                        }),
                ]),

            Section::make('المخزون')
                ->schema([
                    CheckboxList::make('inventory_permissions')
                        ->label('')
                        ->options($group([
                            'items',
                            'categories',
                            'units',
                            'warehouses',
                            'opening_stock_vouchers',
                            'goods_receipt_vouchers',
                            'goods_issue_vouchers',
                            'stock_balances',
                        ]))
                        ->columns(3)
                        ->bulkToggleable()
                        ->searchable()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (CheckboxList $component, $record): void {
                            $allowed = array_keys($component->getOptions());

                            $component->state(
                                array_values(
                                    array_intersect(
                                        $record?->permissions ?? [],
                                        $allowed
                                    )
                                )
                            );
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get): void {
                            self::mergePermissions($set, $get);
                        }),
                ]),

            Section::make('الحسابات والبنوك')
                ->schema([
                    CheckboxList::make('accounting_permissions')
                        ->label('')
                        ->options($group([
                            'treasuries',
                            'banks',
                            'bank_accounts',
                            'bank_transfers',
                            'bank_statements',
                            'bank_checks',
                            'accounts',
                            'journal_entries',
                            'due_obligations',
                            'octram_entries',
                            'cash_advances',
                        ]))
                        ->columns(3)
                        ->bulkToggleable()
                        ->searchable()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (CheckboxList $component, $record): void {
                            $allowed = array_keys($component->getOptions());

                            $component->state(
                                array_values(
                                    array_intersect(
                                        $record?->permissions ?? [],
                                        $allowed
                                    )
                                )
                            );
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get): void {
                            self::mergePermissions($set, $get);
                        }),
                ]),

            Section::make('التقارير')
                ->schema([
                    CheckboxList::make('report_permissions')
                        ->label('')
                        ->options($group([
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
                            'customer_statements',
                            'supplier_statements',
                            'dashboard',
                        ]))
                        ->columns(3)
                        ->bulkToggleable()
                        ->searchable()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (CheckboxList $component, $record): void {
                            $allowed = array_keys($component->getOptions());

                            $component->state(
                                array_values(
                                    array_intersect(
                                        $record?->permissions ?? [],
                                        $allowed
                                    )
                                )
                            );
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get): void {
                            self::mergePermissions($set, $get);
                        }),
                ]),

            Section::make('إدارة النظام')
                ->schema([
                    CheckboxList::make('system_permissions')
                        ->label('')
                        ->options($group([
                            'users',
                            'roles',
                            'company_settings',
                            'documents',
                        ]))
                        ->columns(3)
                        ->bulkToggleable()
                        ->searchable()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (CheckboxList $component, $record): void {
                            $allowed = array_keys($component->getOptions());

                            $component->state(
                                array_values(
                                    array_intersect(
                                        $record?->permissions ?? [],
                                        $allowed
                                    )
                                )
                            );
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get): void {
                            self::mergePermissions($set, $get);
                        }),
                ]),

            /*
             * الحقل الحقيقي الذي يتم حفظه في قاعدة البيانات.
             */
            CheckboxList::make('permissions')
                ->label('')
                ->options(PermissionRegistry::permissionOptions())
                ->hidden(),
        ]);
    }

    private static function mergePermissions($set, $get): void
    {
        $permissions = collect([
            ...($get('sales_permissions') ?? []),
            ...($get('purchase_permissions') ?? []),
            ...($get('inventory_permissions') ?? []),
            ...($get('accounting_permissions') ?? []),
            ...($get('report_permissions') ?? []),
            ...($get('system_permissions') ?? []),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $set('permissions', $permissions);
    }
}
