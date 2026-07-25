<?php

namespace App\Filament\Resources\SalesInvoices\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('document_number')
                    ->label('رقم المستند')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('electronic_invoice_number')
                    ->label('رقم الفاتورة الإلكترونية')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->label('تاريخ الفاتورة')
                    ->date()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->state(fn ($record): float => $record->totalAmount())
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('paid_amount')
                    ->label('المحصل')
                    ->state(fn ($record): float => $record->paidAmount())
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->state(fn ($record): float => $record->remainingAmount())
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('payment_status')
                    ->label('حالة السداد')
                    ->state(fn ($record): string => match ($record->paymentStatus()) {
                        'paid' => 'مسددة بالكامل',
                        'partially_paid' => 'مسددة جزئياً',
                        default => 'غير مسددة',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'مسددة بالكامل' => 'success',
                        'مسددة جزئياً' => 'warning',
                        default => 'danger',
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
