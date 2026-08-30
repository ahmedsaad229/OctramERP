<?php

namespace App\Filament\Resources\Units\Tables;

use App\Filament\Actions\ProtectedDeleteAction;

use App\Filament\Actions\ProtectedDeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitsTable
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
                    ->label('اسم الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('short_name')
                    ->label('الاختصار')
                    ->searchable(),

                IconColumn::make('active')
                    ->label('نشط')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                ProtectedDeleteAction::make()
                    ->iconButton()
                    ->tooltip('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ProtectedDeleteBulkAction::make(),
                ]),
            ]);
    }
}
