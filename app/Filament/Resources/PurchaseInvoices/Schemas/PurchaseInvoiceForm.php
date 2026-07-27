<?php

namespace App\Filament\Resources\PurchaseInvoices\Schemas;

use App\Enums\PaymentType;
use App\Enums\TaxType;
use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Services\CompanyTaxSetting;
use App\Services\DocumentTaxCalculator;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\PurchaseInvoiceService;
use App\Support\DocumentFieldPresentation;
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

class PurchaseInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        $schema->columns(1);

        return $schema->components([
            Section::make('بيانات الفاتورة')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])->schema([
                        TextInput::make('code')
                            ->label('رقم المستند')
                            ->placeholder('سيتم إنشاؤه تلقائياً')
                            ->disabled()
                            ->dehydrated(false),

                        DatePicker::make('invoice_date')
                            ->label('التاريخ')
                            ->required()
                            ->default(now()),

                        TextInput::make('invoice_number')
                            ->label('رقم فاتورة لدى المورد')
                            ->required()
                            ->maxLength(255),

                        Select::make('payment_type')
                            ->label('نوع التعامل')
                            ->options(PaymentType::options())
                            ->default(PaymentType::Cash->value)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                if ($state === PaymentType::Cash->value) {
                                    $set('due_date', null);
                                }
                            }),

                        DatePicker::make('due_date')
                            ->label('تاريخ الاستحقاق')
                            ->visible(fn (Get $get): bool => $get('payment_type') === PaymentType::Credit->value)
                            ->required(fn (Get $get): bool => $get('payment_type') === PaymentType::Credit->value)
                            ->minDate(fn (Get $get): mixed => $get('invoice_date')),

                        Select::make('supplier_id')
                            ->label('المورد')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->validationMessages(['required' => 'يجب اختيار المورد.'])
                            ->afterStateUpdated(function (Set $set): void {
                                $set('supplier_purchase_order_id', null);
                                $set('items', []);
                            }),

                        Select::make('supplier_purchase_order_id')
                            ->label('أمر التوريد')
                            ->options(fn (Get $get, ?PurchaseInvoice $record): array => app(PurchaseInvoiceService::class)
                                ->purchaseOrderOptions(
                                    $get('supplier_id'),
                                    $record?->supplier_purchase_order_id,
                                ))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(fn (Get $get): bool => blank($get('supplier_id')))
                            ->afterStateUpdated(function (
                                Set $set,
                                mixed $state,
                                ?PurchaseInvoice $record,
                            ): void {
                                if (! $state) {
                                    $set('items', []);

                                    return;
                                }

                                $payload = app(PurchaseInvoiceService::class)
                                    ->purchaseOrderSelectionPayload((int) $state, $record);

                                foreach ($payload as $field => $value) {
                                    $set($field, $value);
                                }
                            }),

                        Select::make('warehouse_id')
                            ->label('المخزن')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('tax_type')
                            ->label('الضريبة')
                            ->options([
                                TaxType::Vat14->value => 'ضريبة قيمة مضافة 14%',
                                TaxType::None->value => 'بدون ضريبة',
                            ])
                            ->default(fn (): string => app(CompanyTaxSetting::class)->resolve()->value)
                            ->native(false)
                            ->live(),

                    ])->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),

            Section::make('أصناف الفاتورة')
                ->schema([
                    Repeater::make('items')
                        ->label('أصناف الفاتورة')
                        ->schema([
                            Grid::make([
                                'default' => 1,
                                'md' => 6,
                                'xl' => 13,
                            ])->schema([
                                Hidden::make('supplier_purchase_order_item_id'),
                                Hidden::make('item_code')->dehydrated(false),
                                Hidden::make('item_name')->dehydrated(false),
                                Hidden::make('unit_id')->dehydrated(false),
                                Hidden::make('unit_name')->dehydrated(false),
                                Hidden::make('ordered_quantity')->dehydrated(false),
                                Hidden::make('previously_invoiced_quantity')->dehydrated(false),
                                Hidden::make('remaining_quantity')->dehydrated(false),
                                Select::make('item_id')
                                    ->label('الصنف')
                                    ->options(fn (): array => Item::query()
                                        ->where('active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn (Get $get): bool => filled($get('../../supplier_purchase_order_id')))
                                    ->dehydrated()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 6,
                                        'xl' => 5,
                                    ]),

                                Placeholder::make('item_code_display')
                                    ->label('كود الصنف')
                                    ->content(fn (Get $get): string => $get('item_code') ?: '—')
                                    ->extraAttributes(DocumentFieldPresentation::itemCode())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter(),

                                Placeholder::make('unit_name_display')
                                    ->label('الوحدة')
                                    ->content(fn (Get $get): string => $get('unit_name') ?: '—')
                                    ->extraAttributes(DocumentFieldPresentation::unit())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter(),

                                Placeholder::make('ordered_quantity_display')
                                    ->label('الكمية بأمر التوريد')
                                    ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay($get('ordered_quantity')))
                                    ->extraAttributes(DocumentFieldPresentation::stock())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter()
                                    ->visible(fn (Get $get): bool => filled($get('supplier_purchase_order_item_id'))),

                                Placeholder::make('previously_invoiced_quantity_display')
                                    ->label('الكمية المفوترة سابقًا')
                                    ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay($get('previously_invoiced_quantity')))
                                    ->extraAttributes(DocumentFieldPresentation::stock())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter()
                                    ->visible(fn (Get $get): bool => filled($get('supplier_purchase_order_item_id'))),

                                Placeholder::make('remaining_quantity_display')
                                    ->label('المتبقي قبل هذه الفاتورة')
                                    ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay($get('remaining_quantity')))
                                    ->extraAttributes(DocumentFieldPresentation::stock())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter()
                                    ->visible(fn (Get $get): bool => filled($get('supplier_purchase_order_item_id'))),

                                Placeholder::make('warehouse_balance')
                                    ->label('رصيد المخزن')
                                    ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay(
                                        app(InventoryService::class)->warehouseBalance(
                                            $get('../../warehouse_id'),
                                            $get('item_id'),
                                        ),
                                    ))
                                    ->extraAttributes(DocumentFieldPresentation::stock())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter()
                                    ->visible(fn (Get $get): bool => filled($get('supplier_purchase_order_item_id'))),

                                TextInput::make('quantity')
                                    ->label('كمية الفاتورة')
                                    ->type('text')
                                    ->formatStateUsing(fn (mixed $state): ?string => QuantityFormatter::normalizeForInput($state))
                                    ->mutateStateForValidationUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                                    ->dehydrateStateUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                                    ->inputMode('decimal')
                                    ->extraInputAttributes(QuantityFormatter::inputAttributes())
                                    ->default(1)
                                    ->rules(['numeric', 'gt:0'])
                                    ->afterStateUpdated(fn (TextInput $component, mixed $state) => $component->state(
                                        QuantityFormatter::normalizeForInput($state) ?? $state,
                                    ))
                                    ->maxValue(fn (Get $get): ?float => filled($get('supplier_purchase_order_item_id'))
                                        ? (float) $get('remaining_quantity')
                                        : null)
                                    ->required()
                                    ->validationMessages([
                                        'gt' => 'يجب أن تكون كمية الفاتورة أكبر من صفر.',
                                        'max' => 'كمية الفاتورة أكبر من الكمية المتبقية في أمر التوريد.',
                                    ])
                                    ->live()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                TextInput::make('unit_cost')
                                    ->label('تكلفة الوحدة')
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
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                Placeholder::make('total')
                                    ->label('الإجمالي')
                                    ->content(fn (Get $get): string => self::money(
                                        (float) $get('quantity') * (float) $get('unit_cost'),
                                    ))
                                    ->extraAttributes(DocumentFieldPresentation::money())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 2,
                                    ]),
                            ])->columnSpanFull(),

                            Textarea::make('notes')
                                ->label('ملاحظات')
                                ->columnSpanFull(),
                        ])
                        ->defaultItems(1)
                        ->addActionLabel('إضافة صنف')
                        ->addable(fn (Get $get): bool => blank($get('supplier_purchase_order_id')))
                        ->collapsible()
                        ->cloneable(fn (Get $get): bool => blank($get('supplier_purchase_order_id')))
                        ->columnSpanFull(),

                    Grid::make(['default' => 1, 'md' => 5])->schema([
                        Placeholder::make('invoice_subtotal')->label('الإجمالي الفرعي')
                            ->content(fn (Get $get): string => self::money(self::subtotal($get)))
                            ->extraAttributes(DocumentFieldPresentation::money())
                            ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                            ->alignCenter(),
                        TextInput::make('discount_amount')->label('الخصم')->numeric()->minValue(0)->default(0)->live(),
                        Placeholder::make('invoice_taxable')->label('صافي قبل الضريبة')
                            ->content(fn (Get $get): string => self::money(self::calculation($get)['taxable_amount']))
                            ->extraAttributes(DocumentFieldPresentation::money())
                            ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                            ->alignCenter(),
                        Placeholder::make('invoice_tax')->label('الضريبة')
                            ->content(fn (Get $get): string => self::money(self::calculation($get)['tax_amount']))
                            ->extraAttributes(DocumentFieldPresentation::money())
                            ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                            ->alignCenter(),
                        Placeholder::make('invoice_total')->label('الإجمالي النهائي')
                            ->content(fn (Get $get): string => self::money(self::calculation($get)['total']))
                            ->extraAttributes(DocumentFieldPresentation::money())
                            ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                            ->alignCenter(),
                    ])->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),

            Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    private static function subtotal(Get $get): float
    {
        return (float) collect($get('items') ?? [])->sum(
            fn (array $item): float => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_cost'] ?? 0),
        );
    }

    /** @return array{taxable_amount: float, tax_amount: float, total: float} */
    private static function calculation(Get $get): array
    {
        return app(DocumentTaxCalculator::class)->calculate(
            self::subtotal($get),
            (float) $get('discount_amount'),
            TaxType::tryFrom((string) $get('tax_type')) ?? TaxType::None,
        );
    }

    private static function money(float $amount): string
    {
        return number_format($amount, 2).' ج.م';
    }
}
