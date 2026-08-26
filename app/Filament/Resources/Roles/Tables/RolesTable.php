<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('الدور')->searchable()->sortable(),
            TextColumn::make('description')->label('الوصف')->placeholder('—')->wrap(),
            TextColumn::make('permissions')->label('عدد الصلاحيات')->state(fn ($record): int => count($record->permissions ?? []))->badge(),
            TextColumn::make('users_count')->label('المستخدمون')->counts('users')->badge(),
            IconColumn::make('is_system')->label('دور نظام')->boolean(),
        ])->recordActions([
            EditAction::make(),
            DeleteAction::make()->visible(fn ($record): bool => ! $record->is_system && $record->users()->doesntExist()),
        ]);
    }
}
