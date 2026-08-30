<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use App\Filament\Actions\ProtectedDeleteAction;


use Filament\Actions\Action;
use App\Models\PurchaseRequest;
use App\Support\QuantityFormatter;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByDesc('request_date')
                ->orderByDesc('id'))
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['items.item', 'requestedBy', 'warehouse'])
                ->withCount('items'))
            ->columns([
                TextColumn::make('code')->label('رقم الطلب')->searchable()->sortable(),
                TextColumn::make('request_date')->label('تاريخ الطلب')->date()->sortable(),
                TextColumn::make('required_date')->label('تاريخ الاحتياج')->date()->placeholder('—')->sortable(),
                TextColumn::make('warehouse.name')->label('المخزن')->placeholder('—')->searchable(),
                TextColumn::make('requestedBy.name')->label('طالب الشراء')->placeholder('—')->searchable(),
                TextColumn::make('department')->label('الإدارة / القسم')->searchable()->placeholder('—'),
                TextColumn::make('purpose')
                    ->label('الغرض')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('items.item.code')
                    ->label('أكواد الأصناف')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('items.item.name')
                    ->label('الأصناف')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('items_count')->label('عدد الأصناف')->badge(),
                TextColumn::make('requested_quantity')
                    ->label('إجمالي المطلوب')
                    ->state(fn (PurchaseRequest $record): float => $record->totalRequestedQuantity())
                    ->formatStateUsing(fn (mixed $state): string => QuantityFormatter::formatForDisplay($state))
                    ->extraAttributes(QuantityFormatter::displayAttributes()),
                TextColumn::make('ordered_quantity')
                    ->label('تم إصدار أوامر به')
                    ->state(fn (PurchaseRequest $record): float => $record->orderedQuantity())
                    ->formatStateUsing(fn (mixed $state): string => QuantityFormatter::formatForDisplay($state))
                    ->extraAttributes(QuantityFormatter::displayAttributes()),
                TextColumn::make('remaining_quantity')
                    ->label('المتبقي')
                    ->state(fn (PurchaseRequest $record): float => $record->remainingQuantity())
                    ->formatStateUsing(fn (mixed $state): string => QuantityFormatter::formatForDisplay($state))
                    ->extraAttributes(QuantityFormatter::displayAttributes()),
                TextColumn::make('procurement_status')
                    ->label('حالة التوريد')
                    ->state(fn (PurchaseRequest $record): string => $record->procurementStatusLabel())
                    ->badge()
                    ->color(fn (PurchaseRequest $record): string => match ($record->procurementStatus()) {
                        PurchaseRequest::STATUS_PARTIALLY_ORDERED => 'warning',
                        PurchaseRequest::STATUS_FULLY_ORDERED => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Filter::make('request_date')->label('تاريخ الطلب')->schema([
                    DatePicker::make('from')->label('من'),
                    DatePicker::make('until')->label('إلى'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('request_date', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('request_date', '<=', $date))),
                Filter::make('required_date')->label('تاريخ الاحتياج')->schema([
                    DatePicker::make('from')->label('من'),
                    DatePicker::make('until')->label('إلى'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('required_date', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('required_date', '<=', $date))),
                SelectFilter::make('warehouse_id')->label('المخزن')->relationship('warehouse', 'name'),
                SelectFilter::make('requested_by')->label('طالب الشراء')->relationship('requestedBy', 'name'),
                SelectFilter::make('procurement_status')
                    ->label('حالة التوريد')
                    ->options([
                        PurchaseRequest::STATUS_NOT_ORDERED => 'لم يتم إصدار أمر توريد',
                        PurchaseRequest::STATUS_PARTIALLY_ORDERED => 'تم التوريد جزئيًا',
                        PurchaseRequest::STATUS_FULLY_ORDERED => 'تم إصدار أمر توريد بالكامل',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return $query->when($value, fn (Builder $query): Builder => match ($value) {
                            PurchaseRequest::STATUS_NOT_ORDERED => $query->whereDoesntHave('items.purchaseOrderItems'),
                            PurchaseRequest::STATUS_PARTIALLY_ORDERED => $query
                                ->whereHas('items.purchaseOrderItems')
                                ->whereRaw('(SELECT COALESCE(SUM(spoi.ordered_quantity), 0)
                                    FROM supplier_purchase_order_items spoi
                                    INNER JOIN purchase_request_items pri ON pri.id = spoi.purchase_request_item_id
                                    WHERE pri.purchase_request_id = purchase_requests.id)
                                    < (SELECT COALESCE(SUM(pri.requested_quantity), 0)
                                    FROM purchase_request_items pri
                                    WHERE pri.purchase_request_id = purchase_requests.id)'),
                            PurchaseRequest::STATUS_FULLY_ORDERED => $query
                                ->whereRaw('(SELECT COALESCE(SUM(spoi.ordered_quantity), 0)
                                    FROM supplier_purchase_order_items spoi
                                    INNER JOIN purchase_request_items pri ON pri.id = spoi.purchase_request_item_id
                                    WHERE pri.purchase_request_id = purchase_requests.id)
                                    >= (SELECT COALESCE(SUM(pri.requested_quantity), 0)
                                    FROM purchase_request_items pri
                                    WHERE pri.purchase_request_id = purchase_requests.id)'),
                            default => $query,
                        });
                    }),
            ])
            ->recordActions([
                Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(
                        fn (PurchaseRequest $record): string => route(
                            'purchase-requests.print',
                            $record
                        )
                    )
                    ->openUrlInNewTab(),

                EditAction::make(),

                ProtectedDeleteAction::make()
                    ->iconButton()
                    ->tooltip('حذف'),
            ])
            ->emptyStateHeading('لا توجد طلبات شراء');
    }
}
