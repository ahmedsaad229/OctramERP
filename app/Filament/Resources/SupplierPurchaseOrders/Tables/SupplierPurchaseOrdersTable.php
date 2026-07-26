<?php

namespace App\Filament\Resources\SupplierPurchaseOrders\Tables;

use App\Enums\PaymentType;
use App\Enums\TaxType;
use App\Support\QuantityFormatter;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierPurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('order_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['items.item', 'supplier', 'purchaseRequest', 'warehouse'])
                ->withCount('items'))
            ->columns([
                TextColumn::make('code')->label('رقم أمر التوريد')->searchable()->sortable(),
                TextColumn::make('order_date')->label('التاريخ')->date()->sortable(),
                TextColumn::make('supplier.name')->label('المورد')->searchable()->sortable(),
                TextColumn::make('purchaseRequest.code')->label('طلب الشراء')->searchable(),
                TextColumn::make('warehouse.name')->label('المخزن')->placeholder('—'),
                TextColumn::make('expected_delivery_date')->label('التوريد المتوقع')->date()->placeholder('—')->sortable(),
                TextColumn::make('supplier_reference')->label('مرجع المورد')->searchable()->placeholder('—'),
                TextColumn::make('items.item.code')
                    ->label('أكواد الأصناف')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('items.item.name')
                    ->label('الأصناف')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subtotal')->label('الإجمالي الفرعي')->numeric(2),
                TextColumn::make('tax_amount')->label('الضريبة')->numeric(2),
                TextColumn::make('tax_type')->label('نوع الضريبة')
                    ->formatStateUsing(fn (TaxType $state): string => $state->label())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')->label('الإجمالي النهائي')->numeric(2),
                TextColumn::make('items_count')->label('عدد الأصناف')->badge(),
                TextColumn::make('invoiced_quantity')
                    ->label('الكمية المفوترة')
                    ->state(fn ($record): float => $record->invoicedQuantity())
                    ->formatStateUsing(fn (mixed $state): string => QuantityFormatter::formatForDisplay($state))
                    ->extraAttributes(QuantityFormatter::displayAttributes()),
                TextColumn::make('remaining_to_invoice')
                    ->label('المتبقي للفوترة')
                    ->state(fn ($record): float => $record->remainingToInvoiceQuantity())
                    ->formatStateUsing(fn (mixed $state): string => QuantityFormatter::formatForDisplay($state))
                    ->extraAttributes(QuantityFormatter::displayAttributes()),
                TextColumn::make('invoice_conversion_status')
                    ->label('حالة الفوترة')
                    ->state(fn ($record): string => $record->invoiceConversionStatusLabel())
                    ->badge()
                    ->color(fn ($record): string => match ($record->invoiceConversionStatus()) {
                        'partially_invoiced' => 'warning',
                        'fully_invoiced' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('supplier_id')->label('المورد')->relationship('supplier', 'name')->searchable()->preload(),
                SelectFilter::make('purchase_request_id')->label('طلب الشراء')->relationship('purchaseRequest', 'code')->searchable()->preload(),
                SelectFilter::make('warehouse_id')->label('المخزن')->relationship('warehouse', 'name'),
                SelectFilter::make('payment_type')->label('طريقة الدفع')->options(PaymentType::options()),
                SelectFilter::make('tax_type')->label('نوع الضريبة')->options(TaxType::options()),
                SelectFilter::make('invoice_conversion_status')
                    ->label('حالة الفوترة')
                    ->options([
                        'not_invoiced' => 'غير مفوتر',
                        'partially_invoiced' => 'مفوتر جزئيًا',
                        'fully_invoiced' => 'مفوتر بالكامل',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;
                        $invoicedSql = '(SELECT COALESCE(SUM(pii.quantity), 0)
                            FROM purchase_invoice_items pii
                            INNER JOIN supplier_purchase_order_items spoi
                                ON spoi.id = pii.supplier_purchase_order_item_id
                            WHERE spoi.supplier_purchase_order_id = supplier_purchase_orders.id)';
                        $orderedSql = '(SELECT COALESCE(SUM(spoi.ordered_quantity), 0)
                            FROM supplier_purchase_order_items spoi
                            WHERE spoi.supplier_purchase_order_id = supplier_purchase_orders.id)';

                        return match ($status) {
                            'not_invoiced' => $query->whereRaw("{$invoicedSql} = 0"),
                            'partially_invoiced' => $query
                                ->whereRaw("{$invoicedSql} > 0")
                                ->whereRaw("{$invoicedSql} < {$orderedSql}"),
                            'fully_invoiced' => $query->whereRaw("{$invoicedSql} >= {$orderedSql}"),
                            default => $query,
                        };
                    }),
                Filter::make('order_date')->label('تاريخ الأمر')->schema([
                    DatePicker::make('from')->label('من'),
                    DatePicker::make('until')->label('إلى'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('order_date', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('order_date', '<=', $date))),
                Filter::make('expected_delivery_date')->label('تاريخ التوريد المتوقع')->schema([
                    DatePicker::make('from')->label('من'),
                    DatePicker::make('until')->label('إلى'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('expected_delivery_date', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('expected_delivery_date', '<=', $date))),
            ])
            ->recordActions([EditAction::make()])
            ->emptyStateHeading('لا توجد أوامر توريد');
    }
}
