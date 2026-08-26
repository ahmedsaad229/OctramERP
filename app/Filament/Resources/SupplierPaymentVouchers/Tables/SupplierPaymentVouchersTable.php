<?php

namespace App\Filament\Resources\SupplierPaymentVouchers\Tables;


use Filament\Actions\Action;
use App\Enums\PaymentMethod;
use App\Models\SupplierPaymentVoucher;
use App\Support\ArabicMoney;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierPaymentVouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->where('payment_type', SupplierPaymentVoucher::TYPE_SUPPLIER)
                ->with([
                    'supplier',
                    'treasury',
                    'allocations.purchaseInvoice.items',
                    'allocations.purchaseInvoice.supplierPaymentAllocations.supplierPaymentVoucher',
                ])
                ->orderByDesc('voucher_date')
                ->orderByDesc('id'))
            ->columns([
                TextColumn::make('document_number')
                    ->label('رقم المستند')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('voucher_date')
                    ->label('التاريخ')
                    ->date('Y/m/d')
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchase_invoice_number')
                    ->label('رقم فاتورة المورد')
                    ->state(fn (SupplierPaymentVoucher $record): string => $record
                        ->relatedPurchaseInvoice()?->invoice_number ?? '—')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas(
                            'allocations.purchaseInvoice',
                            fn (Builder $query): Builder => $query->where('invoice_number', 'like', "%{$search}%"),
                        )),
                TextColumn::make('purchase_invoice_code')
                    ->label('رقم المستند الداخلي')
                    ->state(fn (SupplierPaymentVoucher $record): string => $record
                        ->relatedPurchaseInvoice()?->code ?? '—')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas(
                            'allocations.purchaseInvoice',
                            fn (Builder $query): Builder => $query->where('code', 'like', "%{$search}%"),
                        )),
                TextColumn::make('treasury.name')
                    ->label('الخزينة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label())
                    ->badge(),
                TextColumn::make('invoice_total')
                    ->label('إجمالي الفاتورة')
                    ->state(fn (SupplierPaymentVoucher $record): string => ArabicMoney::format(
                        $record->paymentSummaryBefore()['invoice_total'],
                    )),
                TextColumn::make('previously_paid')
                    ->label('المدفوع سابقًا')
                    ->state(fn (SupplierPaymentVoucher $record): string => ArabicMoney::format(
                        $record->paymentSummaryBefore()['previously_paid'],
                    )),
                TextColumn::make('amount')
                    ->label('الدفعة الحالية')
                    ->formatStateUsing(fn (mixed $state): string => ArabicMoney::format($state))
                    ->sortable(),
                TextColumn::make('remaining_after_payment')
                    ->label('المتبقي')
                    ->state(fn (SupplierPaymentVoucher $record): string => ArabicMoney::format(
                        $record->paymentSummaryBefore()['remaining_after_payment'],
                    )),
                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label('المورد')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('treasury_id')
                    ->label('الخزينة')
                    ->relationship('treasury', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('payment_method')
                    ->label('طريقة الدفع')
                    ->options(PaymentMethod::options()),
                Filter::make('voucher_date')
                    ->label('نطاق التاريخ')
                    ->schema([
                        DatePicker::make('from')->label('من'),
                        DatePicker::make('until')->label('إلى'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, mixed $date): Builder => $query->whereDate('voucher_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, mixed $date): Builder => $query->whereDate('voucher_date', '<=', $date))),
            ])
            ->recordActions([
                                Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(
                        fn (SupplierPaymentVoucher $record): string => route(
                            'supplier-payment-vouchers.print',
                            $record
                        )
                    )
                    ->openUrlInNewTab(),
EditAction::make(),
            ]);
    }
}
