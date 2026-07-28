<?php

namespace App\Filament\Resources\SalesInvoices\Schemas;

use App\Enums\PaymentType;
use App\Enums\TaxType;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Services\CompanyTaxSetting;
use App\Services\CustomerPurchaseOrderConversionService;
use App\Services\DocumentTaxCalculator;
use App\Services\Inventory\InventoryService;
use App\Services\SalesQuotationConversionService;
use App\Support\DocumentFieldPresentation;
use App\Support\QuantityFormatter;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SalesInvoiceForm
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
                        TextInput::make('document_number')
                            ->label('رقم المستند')
                            ->placeholder('سيتم إنشاؤه تلقائياً')
                            ->disabled()
                            ->dehydrated(false),

                        DatePicker::make('invoice_date')
                            ->label('تاريخ الفاتورة')
                            ->default(now())
                            ->native(false)
                            ->required(),

                        TextInput::make('electronic_invoice_number')
                            ->label('رقم الفاتورة الإلكترونية')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->validationMessages([
                                'required' => 'رقم الفاتورة الإلكترونية مطلوب.',
                                'integer' => 'رقم الفاتورة الإلكترونية يجب أن يكون رقماً صحيحاً.',
                                'min' => 'رقم الفاتورة الإلكترونية يجب أن يكون أكبر من صفر.',
                            ]),

                        Select::make('sales_quotation_id')
                            ->label('عرض السعر')
                            ->options(fn (Get $get, ?SalesInvoice $record): array => app(SalesQuotationConversionService::class)->options(
                                filled($get('customer_id')) ? (int) $get('customer_id') : null,
                                $record?->sales_quotation_id,
                            ))
                            ->default(fn (): ?int => request()->integer('sales_quotation') ?: null)
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state, ?SalesInvoice $record): void {
                                if (blank($state)) {
                                    return;
                                }
                                foreach (app(SalesQuotationConversionService::class)->payload((int) $state, $record?->getKey()) as $field => $value) {
                                    $set($field, $value);
                                }
                            }),

                        Select::make('customer_purchase_order_id')
                            ->label('أمر توريد العميل')
                            ->options(fn (Get $get, ?SalesInvoice $record): array => app(CustomerPurchaseOrderConversionService::class)->options(
                                filled($get('customer_id')) ? (int) $get('customer_id') : null,
                                $record?->customer_purchase_order_id,
                            ))
                            ->searchable()->preload()->live()
                            ->afterStateUpdated(function (Set $set, mixed $state, ?SalesInvoice $record): void {
                                $set('order_import_lines', blank($state) ? [] : app(CustomerPurchaseOrderConversionService::class)
                                    ->lines((int) $state, $record?->getKey()));
                            }),

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
                            ->minDate(fn (Get $get): mixed => $get('invoice_date'))
                            ->native(false),

                        Select::make('customer_id')
                            ->label('العميل')
                            ->relationship(
                                'customer',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->where('active', true),
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('customer_purchase_order_id', null);
                                $set('order_import_lines', []);
                            })
                            ->required(),

                        Select::make('warehouse_id')
                            ->label('المخزن')
                            ->relationship(
                                'warehouse',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->where('active', true),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
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

                    ])->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),

            Section::make('استيراد بنود أمر توريد العميل')
                ->visible(fn (Get $get): bool => filled($get('customer_purchase_order_id')))
                ->schema([
                    Repeater::make('order_import_lines')->label('البنود المتاحة')->schema([
                        Hidden::make('customer_purchase_order_item_id'), Hidden::make('item_id'),
                        Hidden::make('unit_id'), Hidden::make('remaining_quantity'), Hidden::make('description'),
                        Grid::make(['default' => 1, 'md' => 4, 'xl' => 10])->schema([
                            Toggle::make('selected')->label('اختيار'),
                            TextInput::make('item_code')->label('كود الصنف')->readOnly()->dehydrated(),
                            TextInput::make('item_name')->label('الصنف')->readOnly()->dehydrated()->columnSpan(2),
                            TextInput::make('unit_name')->label('الوحدة')->readOnly()->dehydrated(),
                            TextInput::make('ordered_quantity')->label('المطلوب')->readOnly()->dehydrated(),
                            TextInput::make('executed_quantity')->label('المنفذ سابقًا')->readOnly()->dehydrated(),
                            TextInput::make('import_quantity')->label('كمية الاستيراد')->type('text')->inputMode('decimal')
                                ->extraInputAttributes(QuantityFormatter::inputAttributes())->rules(['numeric', 'gt:0']),
                            TextInput::make('unit_price')->label('سعر الوحدة')->type('text')->inputMode('decimal')
                                ->extraInputAttributes(QuantityFormatter::inputAttributes())->rules(['numeric', 'gte:0']),
                            TextInput::make('tax_rate')->label('الضريبة')->readOnly()->dehydrated(),
                        ]),
                    ])->addable(false)->deletable(false)->reorderable(false),
                    Actions::make([
                        Action::make('import_order_lines')->label('استيراد البنود المحددة')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->action(fn (Get $get, Set $set) => $set('items', app(CustomerPurchaseOrderConversionService::class)
                                ->invoiceItems($get('order_import_lines') ?? []))),
                    ]),
                ])->columnSpanFull(),

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
                                Hidden::make('sales_quotation_item_id'),
                                Hidden::make('customer_purchase_order_item_id'),
                                Hidden::make('unit_id'),
                                Hidden::make('discount_amount'),
                                Hidden::make('tax_amount'),
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
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                                        $set(
                                            'unit_price',
                                            Item::query()->whereKey($state)->value('sale_price') ?? 0,
                                        );
                                    })
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 6,
                                        'xl' => 4,
                                    ]),

                                TextInput::make('quantity')
                                    ->label('الكمية')
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
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                Placeholder::make('warehouse_stock_balance')
                                    ->label('الرصيد بالمخزن')
                                    ->content(fn (Get $get, ?SalesInvoice $record): string => QuantityFormatter::formatForDisplay(
                                        app(InventoryService::class)->availableForSalesInvoice(
                                            (int) $get('../../warehouse_id'),
                                            (int) $get('item_id'),
                                            $record?->getKey(),
                                        ),
                                    ))
                                    ->extraAttributes(DocumentFieldPresentation::stock())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 2,
                                    ]),

                                TextInput::make('unit_price')
                                    ->label('سعر البيع')
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
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                Placeholder::make('line_total_display')
                                    ->label('الإجمالي')
                                    ->content(fn (Get $get): string => self::money(
                                        (float) $get('quantity') * (float) $get('unit_price'),
                                    ))
                                    ->extraAttributes(DocumentFieldPresentation::money())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 1,
                                    ]),

                                TextInput::make('notes')
                                    ->label('ملاحظات')
                                    ->columnSpanFull(),
                            ])->columnSpanFull(),
                        ])
                        ->defaultItems(1)
                        ->minItems(1)
                        ->addActionLabel('إضافة صنف')
                        ->reorderable(false)
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
            fn (array $item): float => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0),
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
