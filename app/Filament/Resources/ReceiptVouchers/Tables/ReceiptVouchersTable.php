<?php

namespace App\Filament\Resources\ReceiptVouchers\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReceiptVouchersTable
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
                TextColumn::make('date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('treasury.name')
                    ->label('الخزينة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('إجمالي المبلغ')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
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
