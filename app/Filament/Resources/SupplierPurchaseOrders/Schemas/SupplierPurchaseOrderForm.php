<?php

namespace App\Filament\Resources\SupplierPurchaseOrders\Schemas;

use App\Enums\PaymentType;
use App\Enums\TaxType;
use App\Models\SupplierPurchaseOrder;
use App\Services\CompanyTaxSetting;
use App\Services\DocumentTaxCalculator;
use App\Services\SupplierPurchaseOrderService;
use App\Support\QuantityFormatter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SupplierPurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('بيانات أمر التوريد')->schema([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                    TextInput::make('code')
                        ->label('رقم أمر التوريد')
                        ->placeholder('سيتم إنشاؤه تلقائيًا')
                        ->disabled()
                        ->dehydrated(false),
                    DatePicker::make('order_date')->label('تاريخ أمر التوريد')->default(now())->required(),
                    Select::make('purchase_request_id')
                        ->label('طلب الشراء')
                        ->options(fn (?SupplierPurchaseOrder $record): array => app(SupplierPurchaseOrderService::class)
                            ->purchaseRequestOptions($record?->purchase_request_id))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->validationMessages([
                            'required' => 'يجب اختيار طلب شراء لإنشاء أمر التوريد.',
                        ])
                        ->live()
                        ->helperText('عند تغيير طلب الشراء تُستبدل الأصناف الحالية بالأصناف المتبقية في الطلب الجديد.')
                        ->afterStateUpdated(function (Set $set, mixed $state, ?SupplierPurchaseOrder $record): void {
                            if (! $state) {
                                foreach ([
                                    'warehouse_id',
                                    'request_required_date',
                                    'request_purpose',
                                    'request_department',
                                    'request_requester',
                                ] as $field) {
                                    $set($field, null);
                                }

                                $set('items', []);

                                return;
                            }

                            $payload = app(SupplierPurchaseOrderService::class)->requestSelectionPayload(
                                (int) $state,
                                $record?->getKey(),
                            );

                            foreach ($payload as $field => $value) {
                                $set($field, $value);
                            }
                        }),
                    Select::make('supplier_id')
                        ->label('المورد')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    DatePicker::make('expected_delivery_date')
                        ->label('تاريخ التوريد المتوقع')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->placeholder('01/12/2026')
                        ->extraTriggerAttributes([
                            'dir' => 'ltr',
                        ]),
                    Select::make('payment_type')
                        ->label('طريقة الدفع')
                        ->options(PaymentType::options())
                        ->default(PaymentType::Cash->value)
                        ->live()
                        ->afterStateUpdated(fn (Set $set, mixed $state) => $state === PaymentType::Cash->value ? $set('due_date', null) : null),
                    DatePicker::make('due_date')
                        ->label('تاريخ الاستحقاق')
                        ->visible(fn (Get $get): bool => $get('payment_type') === PaymentType::Credit->value)
                        ->required(fn (Get $get): bool => $get('payment_type') === PaymentType::Credit->value)
                        ->minDate(fn (Get $get) => $get('order_date')),
                    TextInput::make('supplier_reference')->label('مرجع المورد')->maxLength(255),
                ])->columnSpanFull(),
            ]),
            Section::make('بيانات طلب الشراء')->schema([
                Hidden::make('warehouse_id'),
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                    TextInput::make('request_required_date')
                        ->label('تاريخ الاحتياج')
                        ->disabled()
                        ->dehydrated(),
                    TextInput::make('request_department')
                        ->label('الإدارة / القسم')
                        ->disabled()
                        ->dehydrated(),
                    TextInput::make('request_requester')
                        ->label('طالب الشراء')
                        ->disabled()
                        ->dehydrated(),
                    Textarea::make('request_purpose')
                        ->label('الغرض من الطلب')
                        ->disabled()
                        ->dehydrated(),
                ]),
            ]),
            Section::make('أصناف أمر التوريد')->schema([
                Repeater::make('items')
                    ->label('الأصناف')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2, 'xl' => 16])->schema([
                            Hidden::make('purchase_request_item_id'),
                            Hidden::make('item_id'),
                            Hidden::make('unit_id'),
                            Hidden::make('item_code')->dehydrated(false),
                            Hidden::make('item_name')->dehydrated(false),
                            Hidden::make('unit_name')->dehydrated(false),
                            Hidden::make('requested_quantity')->dehydrated(false),
                            Hidden::make('previously_ordered_quantity')->dehydrated(false),
                            Hidden::make('remaining_quantity')->dehydrated(false),
                            Hidden::make('warehouse_balance')->dehydrated(false),
                            Hidden::make('total_balance')->dehydrated(false),
                            Placeholder::make('item_name_display')
                                ->label('الصنف')
                                ->content(fn (Get $get): string => $get('item_name') ?: '—')
                                ->columnSpan(['md' => 2, 'xl' => 4]),
                            Placeholder::make('item_code_display')
                                ->label('كود الصنف')
                                ->content(fn (Get $get): string => $get('item_code') ?: '—')
                                ->columnSpan(['xl' => 2]),
                            Placeholder::make('unit_name_display')
                                ->label('الوحدة')
                                ->content(fn (Get $get): string => $get('unit_name') ?: '—')
                                ->columnSpan(['xl' => 2]),
                            Placeholder::make('requested_quantity_display')
                                ->label('الكمية بطلب الشراء')
                                ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay($get('requested_quantity')))
                                ->extraAttributes(QuantityFormatter::displayAttributes())
                                ->columnSpan(['xl' => 2]),
                            Placeholder::make('previously_ordered_quantity_display')
                                ->label('سبق إصدار أوامر بها')
                                ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay($get('previously_ordered_quantity')))
                                ->extraAttributes(QuantityFormatter::displayAttributes())
                                ->columnSpan(['xl' => 2]),
                            Placeholder::make('remaining_quantity_display')
                                ->label('المتبقي قبل هذا الأمر')
                                ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay($get('remaining_quantity')))
                                ->extraAttributes(QuantityFormatter::displayAttributes())
                                ->columnSpan(['xl' => 2]),
                            TextInput::make('ordered_quantity')
                                ->label('كمية أمر التوريد')
                                ->type('text')
                                ->formatStateUsing(fn (mixed $state): ?string => QuantityFormatter::normalizeForInput($state))
                                ->mutateStateForValidationUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                                ->dehydrateStateUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                                ->inputMode('decimal')
                                ->extraInputAttributes(QuantityFormatter::inputAttributes())
                                ->rules(['numeric', 'gt:0'])
                                ->afterStateUpdated(fn (TextInput $component, mixed $state) => $component->state(
                                    QuantityFormatter::normalizeForInput($state) ?? $state,
                                ))
                                ->maxValue(fn (Get $get): float => (float) $get('remaining_quantity'))
                                ->required()
                                ->live()
                                ->columnSpan(['xl' => 2]),
                            Placeholder::make('warehouse_balance_display')
                                ->label('رصيد المخزن')
                                ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay($get('warehouse_balance')))
                                ->extraAttributes(QuantityFormatter::displayAttributes())
                                ->columnSpan(['xl' => 2]),
                            Placeholder::make('total_balance_display')
                                ->label('إجمالي الرصيد بالمخازن')
                                ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay($get('total_balance')))
                                ->extraAttributes(QuantityFormatter::displayAttributes())
                                ->columnSpan(['xl' => 2]),
                            TextInput::make('unit_price')
                                ->label('سعر الوحدة')
                                ->type('text')
                                ->formatStateUsing(fn (mixed $state): ?string => QuantityFormatter::normalizeForInput($state))
                                ->mutateStateForValidationUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                                ->dehydrateStateUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                                ->inputMode('decimal')
                                ->rules(['numeric', 'gte:0'])
                                ->afterStateUpdated(fn (TextInput $component, mixed $state) => $component->state(
                                    QuantityFormatter::normalizeForInput($state) ?? $state,
                                ))
                                ->extraInputAttributes(QuantityFormatter::inputAttributes())
                                ->minValue(0)
                                ->default(0)
                                ->required()
                                ->live()
                                ->columnSpan(['xl' => 2]),
                            Placeholder::make('line_total')
                                ->label('الإجمالي')
                                ->content(fn (Get $get): string => number_format(
                                    (float) $get('ordered_quantity') * (float) $get('unit_price'),
                                    2,
                                ))
                                ->columnSpan(['xl' => 2]),
                            TextInput::make('notes')->label('ملاحظات')->columnSpan(['md' => 2, 'xl' => 8]),
                        ])->columnSpanFull(),
                    ])
                    ->defaultItems(0)
                    ->addable(false)
                    ->cloneable(false)
                    ->reorderable()
                    ->columnSpanFull(),
            ]),
            Section::make('الإجماليات')->schema([
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    Placeholder::make('calculated_subtotal')
                        ->label('الإجمالي الفرعي')
                        ->content(fn (Get $get): string => self::money(self::subtotal($get('items') ?? []))),
                    TextInput::make('discount_amount')
                        ->label('الخصم')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->live(),
                    Select::make('tax_type')
                        ->label('الضريبة')
                        ->options([
                            TaxType::Vat14->value => 'ضريبة قيمة مضافة 14%',
                            TaxType::None->value => 'بدون ضريبة',
                        ])
                        ->default(fn (): string => app(CompanyTaxSetting::class)->resolve()->value)
                        ->native(false)
                        ->live(),
                    Placeholder::make('calculated_taxable')
                        ->label('صافي قبل الضريبة')
                        ->content(fn (Get $get): string => self::money(self::calculation($get)['taxable_amount'])),
                    Placeholder::make('calculated_tax')
                        ->label('الضريبة')
                        ->content(fn (Get $get): string => self::money(self::calculation($get)['tax_amount'])),
                    Placeholder::make('calculated_total')
                        ->label('الإجمالي النهائي')
                        ->content(fn (Get $get): string => self::money(self::calculation($get)['total'])),
                ]),
            ]),
            Section::make('البيان والملاحظات')->schema([
                Textarea::make('notes')->label('ملاحظات')->rows(3),
            ]),
        ]);
    }

    private static function subtotal(array $items): float
    {
        return (float) collect($items)->sum(
            fn (array $item): float => (float) ($item['ordered_quantity'] ?? 0)
                * (float) ($item['unit_price'] ?? 0),
        );
    }

    private static function money(float $amount): string
    {
        return number_format($amount, 2).' ج.م';
    }

    /** @return array{taxable_amount: float, tax_amount: float, total: float} */
    private static function calculation(Get $get): array
    {
        return app(DocumentTaxCalculator::class)->calculate(
            self::subtotal($get('items') ?? []),
            (float) $get('discount_amount'),
            TaxType::tryFrom((string) $get('tax_type')) ?? TaxType::None,
        );
    }
}
