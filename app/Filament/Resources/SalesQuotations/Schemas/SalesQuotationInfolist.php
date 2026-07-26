<?php

namespace App\Filament\Resources\SalesQuotations\Schemas;

use App\Models\CompanySetting;
use App\Models\SalesQuotation;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class SalesQuotationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            View::make('filament.resources.sales-quotations.company-header')
                ->viewData(fn (SalesQuotation $record): array => [
                    'record' => $record,
                    'settings' => CompanySetting::current(),
                ])
                ->columnSpanFull(),
            Section::make('عرض السعر')
                ->description('ملخص المستند وحالته الحالية.')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                        TextEntry::make('quotation_number')->label('رقم عرض السعر')->badge()->color('info'),
                        TextEntry::make('conversion_status')->label('حالة التحويل')
                            ->state(fn (SalesQuotation $record): string => $record->conversionStatus())
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                SalesQuotation::STATUS_FULLY_CONVERTED => 'محول بالكامل',
                                SalesQuotation::STATUS_PARTIALLY_CONVERTED => 'محول جزئيًا',
                                default => 'غير محول',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                SalesQuotation::STATUS_FULLY_CONVERTED => 'success',
                                SalesQuotation::STATUS_PARTIALLY_CONVERTED => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('expiry')->label('حالة الصلاحية')
                            ->state(fn (SalesQuotation $record): string => $record->expiryLabel())
                            ->badge()
                            ->color(fn (SalesQuotation $record): string => match ($record->expiryStatus()) {
                                'expired' => 'danger',
                                'expiring' => 'warning',
                                'active' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('quotation_date')->label('تاريخ عرض السعر')->date('d/m/Y'),
                        TextEntry::make('valid_until')->label('صالح حتى')->date('d/m/Y')->placeholder('غير محدد'),
                        TextEntry::make('creator.name')->label('أُنشئ بواسطة')->placeholder('—'),
                    ]),
                ]),
            Section::make('بيانات العميل')
                ->icon('heroicon-o-user-circle')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        TextEntry::make('customer.name')->label('العميل'),
                        TextEntry::make('customer.code')->label('كود العميل'),
                        TextEntry::make('warehouse.name')->label('المخزن المرجعي')->placeholder('غير محدد'),
                    ]),
                ]),
            Section::make('الأصناف والأسعار')
                ->icon('heroicon-o-shopping-cart')
                ->schema([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 4, 'xl' => 8])->schema([
                                TextEntry::make('item.name')->label('الصنف')->columnSpan(['md' => 2, 'xl' => 2]),
                                TextEntry::make('item.code')->label('الكود'),
                                TextEntry::make('unit.name')->label('الوحدة'),
                                TextEntry::make('quantity')->label('الكمية')->numeric(decimalPlaces: 2)->alignCenter(),
                                TextEntry::make('unit_price')->label('سعر الوحدة')->formatStateUsing(fn ($state): string => self::money($state))->alignCenter(),
                                TextEntry::make('discount_amount')->label('الخصم')->formatStateUsing(fn ($state): string => self::money($state))->alignCenter(),
                                TextEntry::make('tax_amount')->label('الضريبة')->formatStateUsing(fn ($state): string => self::money($state))->alignCenter(),
                                TextEntry::make('line_total')->label('إجمالي السطر')->formatStateUsing(fn ($state): string => self::money($state))
                                    ->weight('bold')->color('primary')->alignCenter(),
                                TextEntry::make('notes')->label('ملاحظات الصنف')->placeholder('—')->columnSpanFull(),
                            ]),
                        ])
                        ->contained(),
                ]),
            Section::make('الإجماليات')
                ->icon('heroicon-o-calculator')
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 2, 'xl' => 4])->schema([
                        TextEntry::make('subtotal')->label('الإجمالي قبل الخصم')->formatStateUsing(fn ($state): string => self::money($state)),
                        TextEntry::make('discount_amount')->label('إجمالي الخصم')->formatStateUsing(fn ($state): string => self::money($state)),
                        TextEntry::make('tax_amount')->label('ضريبة القيمة المضافة')->formatStateUsing(fn ($state): string => self::money($state)),
                        TextEntry::make('total_amount')->label('الإجمالي النهائي')->formatStateUsing(fn ($state): string => self::money($state))
                            ->weight('bold')->size('lg')->color('primary'),
                    ]),
                ]),
            Section::make('الملاحظات والشروط')
                ->icon('heroicon-o-pencil-square')
                ->schema([
                    TextEntry::make('notes')->label('الملاحظات')->placeholder('لا توجد ملاحظات'),
                    TextEntry::make('terms_and_conditions')->label('الشروط والأحكام')->placeholder('لا توجد شروط إضافية'),
                ])
                ->columns(2)
                ->collapsible(),
            Section::make('معلومات التحويل')
                ->icon('heroicon-o-arrow-path')
                ->schema([
                    TextEntry::make('salesInvoices.document_number')
                        ->label('فواتير البيع المرتبطة')
                        ->badge()
                        ->separator('، ')
                        ->placeholder('لم يتم تحويل العرض إلى فاتورة بيع'),
                ]),
        ]);
    }

    private static function money(mixed $amount): string
    {
        return number_format((float) $amount, 2).' ج.م';
    }
}
