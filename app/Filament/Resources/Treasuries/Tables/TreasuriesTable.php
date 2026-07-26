<?php

namespace App\Filament\Resources\Treasuries\Tables;

use App\Filament\Actions\ProtectedDeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TreasuriesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('الكود')->searchable()->sortable(),
            TextColumn::make('name')->label('اسم الخزينة')->searchable()->sortable(),
            TextColumn::make('opening_balance')->label('الرصيد الافتتاحي')->money('EGP')->sortable(),
            IconColumn::make('is_default')->label('افتراضية')->boolean(),
            IconColumn::make('is_active')->label('نشطة')->boolean(),
        ])->recordActions([
            EditAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([
                ProtectedDeleteBulkAction::make(),
            ]),
        ]);
    }
}
