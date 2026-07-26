<?php

namespace App\Filament\Resources\PurchaseInvoices\Tables;

use App\Enums\PaymentType;
use App\Enums\TaxType;
use App\Models\PurchaseInvoice;
use App\Models\SupplierPurchaseOrder;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class PurchaseInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'items.item',
                    'supplier',
                    'warehouse',
                    'supplierPurchaseOrder.purchaseRequest',
                ])
                ->withCount('items'))
            ->columns([
                TextColumn::make('code')
                    ->label('رقم المستند')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_number')
                    ->label('رقم فاتورة لدى المورد')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplierPurchaseOrder.code')
                    ->label('أمر التوريد')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('supplierPurchaseOrder.purchaseRequest.code')
                    ->label('طلب الشراء')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('supplierPurchaseOrder.supplier_reference')
                    ->label('مرجع المورد')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('items.item.code')
                    ->label('أكواد الأصناف')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('items.item.name')
                    ->label('الأصناف')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->label('الإجمالي النهائي')
                    ->state(fn (PurchaseInvoice $record): float => $record->totalAmount())
                    ->money('EGP'),
                TextColumn::make('tax_type')->label('نوع الضريبة')
                    ->formatStateUsing(fn (TaxType $state): string => $state->label())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tax_amount')->label('قيمة الضريبة')->numeric(2)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_type')
                    ->label('نوع التعامل')
                    ->formatStateUsing(fn (PaymentType $state): string => $state->label())
                    ->badge()
                    ->color(fn (PaymentType $state): string => $state === PaymentType::Cash ? 'success' : 'info'),
                TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('due_status')
                    ->label('حالة الاستحقاق')
                    ->state(fn (PurchaseInvoice $record): string => $record->dueStatusLabel())
                    ->badge()
                    ->color(fn (PurchaseInvoice $record): string => match ($record->dueStatus()) {
                        PurchaseInvoice::DUE_STATUS_UPCOMING => 'info',
                        PurchaseInvoice::DUE_STATUS_TODAY => 'warning',
                        PurchaseInvoice::DUE_STATUS_OVERDUE => 'danger',
                        default => 'success',
                    }),
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('items_count')
                    ->label('عدد الأصناف')
                    ->badge()
                    ->toggleable(),
            ])
            ->filters(self::filters())
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /**
     * @return array<int, Filter|SelectFilter>
     */
    private static function filters(): array
    {
        return [
            SelectFilter::make('payment_type')
                ->label('نوع التعامل')
                ->options(PaymentType::options()),
            SelectFilter::make('tax_type')
                ->label('نوع الضريبة')
                ->options(TaxType::options()),
            SelectFilter::make('supplier_purchase_order_id')
                ->label('أمر التوريد')
                ->options(fn (): array => Schema::hasTable('supplier_purchase_orders')
                    ? SupplierPurchaseOrder::query()->orderBy('code')->pluck('code', 'id')->all()
                    : [])
                ->searchable()
                ->preload()
                ->query(fn (Builder $query, array $data): Builder => $query->when(
                    $data['value'] ?? null,
                    fn (Builder $query, mixed $value): Builder => $query->where(
                        'supplier_purchase_order_id',
                        $value,
                    ),
                )),
            Filter::make('due_date')
                ->label('تاريخ الاستحقاق')
                ->schema([
                    DatePicker::make('from')->label('من'),
                    DatePicker::make('until')->label('إلى'),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, mixed $date): Builder => $query->whereDate('due_date', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, mixed $date): Builder => $query->whereDate('due_date', '<=', $date))),
            Filter::make('overdue')
                ->label('المتأخرة فقط')
                ->query(fn (Builder $query): Builder => $query->dueStatus(PurchaseInvoice::DUE_STATUS_OVERDUE)),
        ];
    }
}
