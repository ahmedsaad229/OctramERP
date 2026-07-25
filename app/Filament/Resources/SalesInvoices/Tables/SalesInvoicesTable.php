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
                    ->state(fn ($record): float => (float) $record->items()->sum('line_total'))
                    ->numeric(decimalPlaces: 2),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
