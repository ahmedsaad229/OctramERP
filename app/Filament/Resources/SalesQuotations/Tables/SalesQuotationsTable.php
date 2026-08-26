<?php

namespace App\Filament\Resources\SalesQuotations\Tables;

use App\Enums\TaxType;
use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Actions\ProtectedDeleteBulkAction;
use App\Filament\Resources\CustomerPurchaseOrders\CustomerPurchaseOrderResource;
use App\Models\CustomerPurchaseOrder;
use App\Models\SalesQuotation;
use App\Services\SalesQuotationService;
use App\Services\SalesQuotationToCustomerPurchaseOrderService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class SalesQuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('quotation_date', 'desc')
            ->columns([
                TextColumn::make('quotation_number')
                    ->label('رقم عرض السعر')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->copyable(),

                TextColumn::make('quotation_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('valid_until')
                    ->label('صالح حتى')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->formatStateUsing(
                        fn ($state): string => number_format((float) $state, 2).' ج.م'
                    )
                    ->alignCenter()
                    ->weight('bold'),

                TextColumn::make('purchase_order_status')
                    ->label('حالة أمر التوريد')
                    ->state(
                        fn (SalesQuotation $record): string => CustomerPurchaseOrder::query()
                            ->where('sales_quotation_id', $record->getKey())
                            ->exists()
                                ? 'converted'
                                : 'not_converted'
                    )
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => match ($state) {
                            'converted' => 'تم إنشاء أمر توريد',
                            default => 'لم يتم التحويل',
                        }
                    )
                    ->color(
                        fn (string $state): string => match ($state) {
                            'converted' => 'success',
                            default => 'gray',
                        }
                    ),

                TextColumn::make('expiry_status')
                    ->label('الصلاحية')
                    ->state(
                        fn (SalesQuotation $record): string => $record->expiryLabel()
                    )
                    ->badge()
                    ->color(
                        fn (SalesQuotation $record): string => match ($record->expiryStatus()) {
                            'expired' => 'danger',
                            'expiring' => 'warning',
                            'active' => 'success',
                            default => 'gray',
                        }
                    ),
            ])
            ->filters([
                Filter::make('quotation_date')
                    ->label('نطاق التاريخ')
                    ->schema([
                        DatePicker::make('from')
                            ->label('من')
                            ->native(false),

                        DatePicker::make('until')
                            ->label('إلى')
                            ->native(false),
                    ])
                    ->query(
                        fn (Builder $query, array $data): Builder => $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query
                                    ->whereDate('quotation_date', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query
                                    ->whereDate('quotation_date', '<=', $date)
                            )
                    ),

                SelectFilter::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('tax_type')
                    ->label('نوع الضريبة')
                    ->options(TaxType::options()),

                SelectFilter::make('purchase_order_status')
                    ->label('حالة أمر التوريد')
                    ->options([
                        'not_converted' => 'لم يتم التحويل',
                        'converted' => 'تم إنشاء أمر توريد',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;

                        if ($status === 'converted') {
                            return $query->whereExists(function ($subQuery): void {
                                $subQuery
                                    ->selectRaw('1')
                                    ->from('customer_purchase_orders')
                                    ->whereColumn(
                                        'customer_purchase_orders.sales_quotation_id',
                                        'sales_quotations.id'
                                    );
                            });
                        }

                        if ($status === 'not_converted') {
                            return $query->whereNotExists(function ($subQuery): void {
                                $subQuery
                                    ->selectRaw('1')
                                    ->from('customer_purchase_orders')
                                    ->whereColumn(
                                        'customer_purchase_orders.sales_quotation_id',
                                        'sales_quotations.id'
                                    );
                            });
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('عرض'),

                Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->iconButton()
                    ->color('gray')
                    ->tooltip('طباعة عرض السعر')
                    ->url(
                        fn (SalesQuotation $record): string => route(
                            'sales-quotations.print',
                            $record
                        )
                    )
                    ->openUrlInNewTab(),


                EditAction::make()
                    ->iconButton()
                    ->tooltip('تعديل'),

                Action::make('convert_to_purchase_order')
                    ->label('تحويل إلى أمر توريد')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->iconButton()
                    ->tooltip('تحويل إلى أمر توريد')
                    ->requiresConfirmation()
                    ->modalHeading('تحويل عرض السعر إلى أمر توريد')
                    ->modalDescription(
                        'سيتم إنشاء أمر توريد جديد ونقل العميل والبنود من عرض السعر.'
                    )
                    ->modalSubmitActionLabel('تنفيذ التحويل')
                    ->visible(
                        fn (SalesQuotation $record): bool => ! CustomerPurchaseOrder::query()
                            ->where('sales_quotation_id', $record->getKey())
                            ->exists()
                    )
                    ->action(function (SalesQuotation $record) {
                        try {
                            $purchaseOrder = app(
                                SalesQuotationToCustomerPurchaseOrderService::class
                            )->convert($record);

                            Notification::make()
                                ->title('تم إنشاء أمر التوريد بنجاح')
                                ->body(
                                    'تم إنشاء أمر التوريد رقم '
                                    .$purchaseOrder->document_number
                                )
                                ->success()
                                ->send();

                            return redirect()->to(
                                CustomerPurchaseOrderResource::getUrl('edit', [
                                    'record' => $purchaseOrder,
                                ])
                            );
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('تعذر إنشاء أمر التوريد')
                                ->body($exception->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            return null;
                        }
                    }),

                Action::make('open_purchase_order')
                    ->label('فتح أمر التوريد')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->iconButton()
                    ->tooltip('فتح أمر التوريد')
                    ->visible(
                        fn (SalesQuotation $record): bool => CustomerPurchaseOrder::query()
                            ->where('sales_quotation_id', $record->getKey())
                            ->exists()
                    )
                    ->url(function (SalesQuotation $record): ?string {
                        $purchaseOrder = CustomerPurchaseOrder::query()
                            ->where('sales_quotation_id', $record->getKey())
                            ->first();

                        if (! $purchaseOrder) {
                            return null;
                        }

                        return CustomerPurchaseOrderResource::getUrl('edit', [
                            'record' => $purchaseOrder,
                        ]);
                    }),

                ProtectedDeleteAction::make()
                    ->iconButton()
                    ->tooltip('حذف')
                    ->using(
                        fn (SalesQuotation $record): bool => app(
                            SalesQuotationService::class
                        )->delete($record)
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ProtectedDeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-document-plus')
            ->emptyStateHeading('لا توجد عروض أسعار حتى الآن')
            ->emptyStateDescription(
                'أنشئ أول عرض سعر للعميل، ثم يمكنك تحويله إلى أمر توريد.'
            )
            ->emptyStateActions([
                CreateAction::make()
                    ->label('إنشاء عرض سعر')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}