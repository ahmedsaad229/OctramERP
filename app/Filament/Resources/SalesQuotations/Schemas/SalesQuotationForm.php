<?php

namespace App\Filament\Resources\SalesQuotations\Schemas;

use App\Enums\TaxType;
use App\Models\Item;
use App\Services\CompanyTaxSetting;
use App\Services\DocumentTaxCalculator;
use App\Services\Inventory\InventoryService;
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

class SalesQuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('بيانات عرض السعر')
                ->description('بيانات العميل وصلاحية العرض وإعداداته الأساسية.')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                        TextInput::make('quotation_number')->label('رقم عرض السعر')->prefixIcon('heroicon-o-hashtag')->disabled()->dehydrated(false)->placeholder('سيتم إنشاؤه تلقائيًا'),
                        DatePicker::make('quotation_date')->label('تاريخ عرض السعر')->prefixIcon('heroicon-o-calendar')->default(now())->native(false)->required()->live(),
                        DatePicker::make('valid_until')->label('صالح حتى')->prefixIcon('heroicon-o-calendar-days')->native(false)->minDate(fn (Get $get) => $get('quotation_date'))
                            ->helperText(fn (Get $get): ?string => self::expiryHint($get('valid_until'))),
                        Select::make('customer_id')->label('العميل')->prefixIcon('heroicon-o-user')->relationship('customer', 'name')->searchable()->preload()->required(),
                        Select::make('warehouse_id')->label('المخزن')->prefixIcon('heroicon-o-building-storefront')->relationship('warehouse', 'name')->searchable()->preload()->live(),
                        Select::make('tax_type')->label('نوع الضريبة')->prefixIcon('heroicon-o-receipt-percent')->options([
                            TaxType::Vat14->value => 'ضريبة قيمة مضافة 14%',
                            TaxType::None->value => 'بدون ضريبة',
                        ])->default(fn (): string => app(CompanyTaxSetting::class)->resolve()->value)->native(false)->live(),
                    ])->columnSpanFull(),
                ]),
            Section::make('الأصناف والأسعار')
                ->description('تفاصيل الأصناف والأسعار والأرصدة المتاحة للعرض فقط.')
                ->icon('heroicon-o-shopping-cart')
                ->schema([
                    Repeater::make('items')->label('الأصناف')->schema([
                        Grid::make(['default' => 1, 'md' => 2, 'xl' => 12])->schema([
                            Select::make('item_id')->label('الصنف')
                                ->options(fn (): array => Item::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()->preload()->required()->distinct()->live()
                                ->afterStateUpdated(function (Set $set, mixed $state): void {
                                    $item = Item::query()->with('unit')->find($state);
                                    $set('unit_id', $item?->unit_id);
                                    $set('unit_name', $item?->unit?->name);
                                    $set('item_code_state', $item?->code);
                                    $set('unit_price', $item?->sale_price ?? 0);
                                })->columnSpan(['md' => 2, 'xl' => 4]),
                            Hidden::make('item_code_state')->dehydrated(false),
                            Placeholder::make('item_code')->label('كود الصنف')
                                ->content(fn (Get $get): string => $get('item_code_state') ?: (Item::find($get('item_id'))?->code ?? '—'))
                                ->alignCenter()
                                ->extraAttributes(self::readOnlyValueAttributes('octram-quotation-item-code-box', ltr: true))
                                ->extraEntryWrapperAttributes(self::centeredWrapperAttributes('octram-quotation-readonly-entry'))
                                ->columnSpan(['xl' => 2]),
                            Hidden::make('unit_id')->required(),
                            Hidden::make('unit_name')->dehydrated(false),
                            Placeholder::make('unit_name_display')
                                ->label('الوحدة')
                                ->content(fn (Get $get): string => $get('unit_name') ?: '—')
                                ->extraAttributes(self::readOnlyValueAttributes('octram-quotation-unit-box'))
                                ->extraEntryWrapperAttributes(self::centeredWrapperAttributes('octram-quotation-readonly-entry'))
                                ->alignCenter()
                                ->columnSpan(['xl' => 1]),
                            self::decimal('quantity', 'الكمية', true)->default(1)->columnSpan(['xl' => 1]),
                            self::decimal('unit_price', 'سعر الوحدة')->default(0)->columnSpan(['xl' => 2]),
                            self::decimal('discount_amount', 'الخصم')->default(0)->columnSpan(['xl' => 2]),
                        ])->columnSpanFull(),
                        Grid::make(['default' => 1, 'sm' => 2, 'xl' => 12])->schema([
                            Placeholder::make('tax_amount_display')
                                ->label('الضريبة')
                                ->content(fn (Get $get): string => self::money(self::line($get)['tax']))
                                ->extraAttributes(self::centeredValueAttributes('octram-quotation-summary-box octram-quotation-money-box'))
                                ->extraEntryWrapperAttributes(self::centeredWrapperAttributes())
                                ->alignCenter()
                                ->columnSpan(['xl' => 3]),
                            Placeholder::make('line_total_display')
                                ->label('الإجمالي')
                                ->content(fn (Get $get): string => self::money(self::line($get)['total']))
                                ->extraAttributes(self::centeredValueAttributes('octram-quotation-summary-box octram-quotation-money-box font-bold text-primary-600 dark:text-primary-400'))
                                ->extraEntryWrapperAttributes(self::centeredWrapperAttributes())
                                ->alignCenter()
                                ->columnSpan(['xl' => 3]),
                            Placeholder::make('warehouse_balance')
                                ->label('رصيد المخزن')
                                ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay(app(InventoryService::class)->warehouseBalance($get('../../warehouse_id'), $get('item_id'))))
                                ->extraAttributes(self::centeredValueAttributes('octram-quotation-summary-box octram-quotation-stock-box'))
                                ->extraEntryWrapperAttributes(self::centeredWrapperAttributes())
                                ->alignCenter()
                                ->columnSpan(['xl' => 3]),
                            Placeholder::make('total_balance')
                                ->label('إجمالي الرصيد')
                                ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay(app(InventoryService::class)->totalBalance($get('item_id'))))
                                ->extraAttributes(self::centeredValueAttributes('octram-quotation-summary-box octram-quotation-stock-box'))
                                ->extraEntryWrapperAttributes(self::centeredWrapperAttributes())
                                ->alignCenter()
                                ->columnSpan(['xl' => 3]),
                        ])->columnSpanFull(),
                        Placeholder::make('stock_warning')->label('تنبيه الرصيد')
                            ->content('الكمية المطلوبة أكبر من الرصيد المتاح بالمخزن.')
                            ->visible(fn (Get $get): bool => filled($get('item_id'))
                                && filled($get('../../warehouse_id'))
                                && (float) $get('quantity') > app(InventoryService::class)->warehouseBalance($get('../../warehouse_id'), $get('item_id')))
                            ->extraAttributes(['class' => 'text-warning-600 dark:text-warning-400'])
                            ->columnSpanFull(),
                        Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
                    ])->defaultItems(1)->minItems(1)->addActionLabel('إضافة صنف')->columnSpanFull(),
                ]),
            Section::make('الإجماليات')
                ->description('ملخص مالي محسوب تلقائيًا ويُعاد احتسابه عند الحفظ.')
                ->icon('heroicon-o-calculator')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 4])->schema([
                        Placeholder::make('subtotal_display')->label('الإجمالي قبل الخصم')->content(fn (Get $get): string => self::money(self::totals($get)['subtotal'])),
                        Placeholder::make('discount_display')->label('إجمالي الخصم')->content(fn (Get $get): string => self::money(self::totals($get)['discount'])),
                        Placeholder::make('tax_display')->label('ضريبة القيمة المضافة')->content(fn (Get $get): string => self::money(self::totals($get)['tax'])),
                        Placeholder::make('total_display')->label('الإجمالي النهائي')->content(fn (Get $get): string => self::money(self::totals($get)['total']))
                            ->extraAttributes(['class' => 'rounded-xl bg-primary-50 p-3 font-bold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300']),
                    ])->columnSpanFull(),
                ]),
            Section::make('الملاحظات والشروط')->description('تفاصيل إضافية تظهر مع عرض السعر.')->icon('heroicon-o-pencil-square')->schema([
                Textarea::make('notes')->label('ملاحظات')->rows(3),
                Textarea::make('terms_and_conditions')->label('الشروط والأحكام')->rows(4),
            ])->columns(2)->collapsible(),
        ]);
    }

    private static function decimal(string $name, string $label, bool $positive = false): TextInput
    {
        return TextInput::make($name)->label($label)->type('text')->inputMode('decimal')
            ->formatStateUsing(fn ($state) => QuantityFormatter::normalizeForInput($state))
            ->mutateStateForValidationUsing(fn ($state) => QuantityFormatter::normalizeForInput($state) ?? $state)
            ->dehydrateStateUsing(fn ($state) => QuantityFormatter::normalizeForInput($state) ?? $state)
            ->extraInputAttributes(QuantityFormatter::inputAttributes())
            ->extraFieldWrapperAttributes(['class' => 'octram-quotation-centered-field'])
            ->rules(['numeric', $positive ? 'gt:0' : 'gte:0'])->required()->live();
    }

    private static function line(Get $get): array
    {
        $base = (float) $get('quantity') * (float) $get('unit_price');
        $discount = min($base, max(0, (float) $get('discount_amount')));
        $calculation = app(DocumentTaxCalculator::class)->calculate($base, $discount, TaxType::tryFrom((string) $get('../../tax_type')) ?? TaxType::None);

        return ['base' => $base, 'discount' => $discount, 'tax' => $calculation['tax_amount'], 'total' => $calculation['total']];
    }

    private static function totals(Get $get): array
    {
        $lines = collect($get('items') ?? [])->map(function (array $item) use ($get): array {
            $base = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
            $discount = min($base, max(0, (float) ($item['discount_amount'] ?? 0)));
            $calc = app(DocumentTaxCalculator::class)->calculate($base, $discount, TaxType::tryFrom((string) $get('tax_type')) ?? TaxType::None);

            return ['base' => $base, 'discount' => $discount, 'tax' => $calc['tax_amount'], 'total' => $calc['total']];
        });

        return ['subtotal' => $lines->sum('base'), 'discount' => $lines->sum('discount'), 'tax' => $lines->sum('tax'), 'total' => $lines->sum('total')];
    }

    private static function money(float $amount): string
    {
        return number_format($amount, 2).' ج.م';
    }

    private static function expiryHint(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        $days = (int) today()->diffInDays($date, false);

        return match (true) {
            $days < 0 => 'انتهت صلاحية العرض منذ '.abs($days).' يوم.',
            $days === 0 => 'تنتهي صلاحية العرض اليوم.',
            $days <= 3 => 'متبقي '.$days.' يوم على انتهاء الصلاحية.',
            default => 'متبقي '.$days.' يوم على انتهاء الصلاحية.',
        };
    }

    /** @return array<string, string> */
    private static function centeredWrapperAttributes(?string $additionalClass = null): array
    {
        return [
            'class' => trim('octram-quotation-centered-entry '.$additionalClass),
        ];
    }

    /** @return array<string, string> */
    private static function centeredValueAttributes(string $class): array
    {
        return [
            'class' => $class,
            'dir' => 'ltr',
            'lang' => 'en',
            'style' => 'direction: ltr; unicode-bidi: isolate;',
        ];
    }

    /** @return array<string, string> */
    private static function readOnlyValueAttributes(string $class, bool $ltr = false): array
    {
        $attributes = [
            'class' => "octram-quotation-readonly-box {$class}",
        ];

        if ($ltr) {
            $attributes['dir'] = 'ltr';
            $attributes['lang'] = 'en';
            $attributes['style'] = 'direction: ltr; unicode-bidi: isolate;';
        }

        return $attributes;
    }
}
