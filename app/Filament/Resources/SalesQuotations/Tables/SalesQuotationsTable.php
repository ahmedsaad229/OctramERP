<?php

namespace App\Filament\Resources\SalesQuotations\Tables;

use App\Enums\TaxType;
use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Actions\ProtectedDeleteBulkAction;
use App\Filament\Resources\SalesInvoices\Pages\CreateSalesInvoice;
use App\Models\SalesQuotation;
use App\Services\SalesQuotationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesQuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('quotation_date', 'desc')->columns([
            TextColumn::make('quotation_number')->label('رقم عرض السعر')->searchable()->sortable()->weight('bold')->color('primary')->copyable(),
            TextColumn::make('quotation_date')->label('التاريخ')->date('d/m/Y')->sortable(),
            TextColumn::make('valid_until')->label('صالح حتى')->date('d/m/Y')->placeholder('—')->sortable(),
            TextColumn::make('customer.name')->label('العميل')->searchable()->sortable(),
            TextColumn::make('total_amount')->label('الإجمالي')->formatStateUsing(fn ($state): string => number_format((float) $state, 2).' ج.م')->alignCenter()->weight('bold'),
            TextColumn::make('conversion_status')->label('حالة التحويل')
                ->state(fn (SalesQuotation $record): string => $record->conversionStatus())
                ->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    SalesQuotation::STATUS_FULLY_CONVERTED => 'محول بالكامل',
                    SalesQuotation::STATUS_PARTIALLY_CONVERTED => 'محول جزئيًا',
                    default => 'غير محول',
                })->color(fn (string $state): string => match ($state) {
                    SalesQuotation::STATUS_FULLY_CONVERTED => 'success',
                    SalesQuotation::STATUS_PARTIALLY_CONVERTED => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('expiry_status')->label('الصلاحية')
                ->state(fn (SalesQuotation $record): string => $record->expiryLabel())
                ->badge()
                ->color(fn (SalesQuotation $record): string => match ($record->expiryStatus()) {
                    'expired' => 'danger',
                    'expiring' => 'warning',
                    'active' => 'success',
                    default => 'gray',
                }),
        ])->filters([
            Filter::make('quotation_date')->label('نطاق التاريخ')->schema([
                DatePicker::make('from')->label('من')->native(false),
                DatePicker::make('until')->label('إلى')->native(false),
            ])->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('quotation_date', '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('quotation_date', '<=', $date))),
            SelectFilter::make('customer_id')->label('العميل')->relationship('customer', 'name')->searchable()->preload(),
            SelectFilter::make('tax_type')->label('نوع الضريبة')->options(TaxType::options()),
            SelectFilter::make('conversion_status')->label('حالة التحويل')->options([
                SalesQuotation::STATUS_NOT_CONVERTED => 'غير محول',
                SalesQuotation::STATUS_PARTIALLY_CONVERTED => 'محول جزئيًا',
                SalesQuotation::STATUS_FULLY_CONVERTED => 'محول بالكامل',
            ])->query(function (Builder $query, array $data): Builder {
                $status = $data['value'] ?? null;

                if (! $status) {
                    return $query;
                }

                $ids = SalesQuotation::query()
                    ->with(['items.salesInvoiceItems'])
                    ->get()
                    ->filter(fn (SalesQuotation $quotation): bool => $quotation->conversionStatus() === $status)
                    ->modelKeys();

                return $query->whereKey($ids);
            }),
        ])->recordActions([
            ViewAction::make()->iconButton()->tooltip('عرض'),
            EditAction::make()->iconButton()->tooltip('تعديل'),
            Action::make('convert')->label('تحويل إلى فاتورة بيع')->icon('heroicon-o-arrow-path')
                ->visible(fn (SalesQuotation $record): bool => ! $record->isFullyConverted())
                ->iconButton()
                ->tooltip('تحويل إلى فاتورة بيع')
                ->url(fn (SalesQuotation $record): string => CreateSalesInvoice::getUrl(['sales_quotation' => $record->getKey()])),
            ProtectedDeleteAction::make()->iconButton()->tooltip('حذف')
                ->using(fn (SalesQuotation $record): bool => app(SalesQuotationService::class)->delete($record)),
        ])->toolbarActions([
            BulkActionGroup::make([
                ProtectedDeleteBulkAction::make(),
            ]),
        ])
            ->emptyStateIcon('heroicon-o-document-plus')
            ->emptyStateHeading('لا توجد عروض أسعار حتى الآن')
            ->emptyStateDescription('أنشئ أول عرض سعر للعميل، ثم يمكنك تحويله لاحقًا إلى فاتورة بيع.')
            ->emptyStateActions([
                CreateAction::make()->label('إنشاء عرض سعر')->icon('heroicon-o-plus'),
            ]);
    }
}
