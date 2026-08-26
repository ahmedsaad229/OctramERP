<?php

namespace App\Filament\Resources\OctramEntries\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OctramEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([

                TextColumn::make('purchase_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('purchaseItem.name')
                    ->label('الصنف')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('purchase_quantity')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),

                TextColumn::make('purchase_price')
                    ->label('سعر الوحدة')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('purchase_tax')
                    ->label('الضريبة')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('purchase_price_including_tax')
                    ->label('السعر شامل')
                    ->money('EGP')
                    ->sortable(),

            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->headerActions([
                Action::make('print_all')
                    ->label('طباعة التقرير')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (): string => route('octram.print-all'))
                    ->openUrlInNewTab(),
            ]);
    }
}
