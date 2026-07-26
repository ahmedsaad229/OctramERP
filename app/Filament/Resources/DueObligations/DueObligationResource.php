<?php

namespace App\Filament\Resources\DueObligations;

use App\Filament\Resources\DueObligations\Pages\ListDueObligations;
use App\Filament\Resources\DueObligations\Tables\DueObligationsTable;
use App\Models\DueObligation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DueObligationResource extends Resource
{
    protected static ?string $model = DueObligation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'الاستحقاقات';

    protected static ?string $modelLabel = 'استحقاق';

    protected static ?string $pluralModelLabel = 'الاستحقاقات';

    protected static string|\UnitEnum|null $navigationGroup = 'الحسابات المالية';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return DueObligationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return DueObligation::queryUnified();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDueObligations::route('/'),
        ];
    }
}
