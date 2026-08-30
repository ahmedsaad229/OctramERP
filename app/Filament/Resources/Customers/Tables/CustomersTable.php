<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Actions\ProtectedDeleteAction;

use App\Filament\Actions\ProtectedDeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
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
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact_person')
                    ->label('اسم المسؤول')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('contact_mobile')
                    ->label('موبايل المسؤول')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('mobile')
                    ->label('الموبايل')
                    ->searchable(),

                TextColumn::make('city')
                    ->label('المدينة')
                    ->searchable(),

                TextColumn::make('opening_balance')
                    ->label('الرصيد')
                    ->money('EGP')
                    ->sortable(),

                IconColumn::make('active')
                    ->label('نشط')
                    ->boolean(),
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
