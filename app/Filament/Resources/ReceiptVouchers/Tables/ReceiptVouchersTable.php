<?php

namespace App\Filament\Resources\ReceiptVouchers\Tables;

use App\Models\ReceiptVoucher;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReceiptVouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'allocations.salesInvoice.items',
                'allocations.salesInvoice.receiptAllocations.receiptVoucher',
            ]))
            ->columns([
                TextColumn::make('document_number')
                    ->label('رقم المستند')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('sales_invoice_electronic_number')
                    ->label('رقم الفاتورة الإلكترونية')
                    ->state(fn (ReceiptVoucher $record): int|string => $record
                        ->relatedSalesInvoice()
                        ?->electronic_invoice_number ?? '—')
                    ->visible(),
                TextColumn::make('sales_invoice_document_number')
                    ->label('كود الفاتورة')
                    ->state(fn (ReceiptVoucher $record): string => $record
                        ->relatedSalesInvoice()
                        ?->document_number ?? '—')
                    ->visible(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('treasury.name')
                    ->label('الخزينة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('previously_paid_before_receipt')
                    ->label('المسدد قبل هذا السند')
                    ->state(fn (ReceiptVoucher $record): float => $record
                        ->paymentSummaryBefore()['previously_paid'])
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('amount')
                    ->label('الدفعة الحالية')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('remaining_after_receipt')
                    ->label('المتبقي بعد هذا السند')
                    ->state(fn (ReceiptVoucher $record): float => $record
                        ->paymentSummaryBefore()['remaining_after_receipt'])
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('notes')
                    ->label('البيان')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
