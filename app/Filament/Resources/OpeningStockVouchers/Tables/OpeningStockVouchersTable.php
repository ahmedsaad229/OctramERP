<?php

namespace App\Filament\Resources\OpeningStockVouchers\Tables;

use App\Filament\Actions\ProtectedDeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OpeningStockVouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')

            ->columns([

                TextColumn::make('code')
                    ->label('رقم المستند')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('voucher_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),

                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('عدد الأصناف')
                    ->badge(),

                IconColumn::make('posted')
                    ->label('مرحل')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->since(),

            ])

            ->filters([

            ])

            ->recordActions([

                EditAction::make(),

            ])

            ->toolbarActions([

                ProtectedDeleteBulkAction::make(),

            ]);
    }
}
