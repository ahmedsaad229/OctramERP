<?php

namespace App\Filament\Resources\StockTransactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('transaction_date', 'desc')

            ->columns([

                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('item.name')
                    ->label('الصنف')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('transaction_type')
                    ->label('نوع الحركة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'opening'      => 'رصيد أول المدة',
                        'purchase'     => 'شراء',
                        'sale'         => 'بيع',
                        'transfer_in'  => 'تحويل وارد',
                        'transfer_out' => 'تحويل صادر',
                        'adjustment'   => 'تسوية',
                        default        => $state,
                    }),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('unit_cost')
                    ->label('تكلفة الوحدة')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('transaction_date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('reference_no')
                    ->label('المرجع')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([

            ])

            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}