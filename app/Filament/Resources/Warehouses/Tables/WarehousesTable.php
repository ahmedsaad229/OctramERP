<?php

namespace App\Filament\Resources\Warehouses\Tables;

use App\Filament\Actions\ProtectedDeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('اسم المخزن')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('manager')
                    ->label('مدير المخزن')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('رقم الهاتف'),

                TextColumn::make('address')
                    ->label('العنوان')
                    ->limit(40),

                IconColumn::make('active')
                    ->label('نشط')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ProtectedDeleteBulkAction::make(),
                ]),
            ]);
    }
}
