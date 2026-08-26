<?php

namespace App\Filament\Resources\OctramEntries;

use App\Filament\Resources\OctramEntries\Pages\CreateOctramEntry;
use App\Filament\Resources\OctramEntries\Pages\EditOctramEntry;
use App\Filament\Resources\OctramEntries\Pages\ListOctramEntries;
use App\Filament\Resources\OctramEntries\Schemas\OctramEntryForm;
use App\Filament\Resources\OctramEntries\Tables\OctramEntriesTable;
use App\Models\OctramEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class OctramEntryResource extends Resource
{
    protected static ?string $model = OctramEntry::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'أوكترام';
    protected static ?string $modelLabel = 'سجل أوكترام';
    protected static ?string $pluralModelLabel = 'أوكترام';
    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';
    protected static ?int $navigationSort = 5;
    protected static string $permissionPrefix = 'octram_entries';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ((bool) ($user->is_admin ?? false)) {
            return true;
        }

        return method_exists($user, 'hasPermission')
            ? $user->hasPermission('octram_entries.view')
            : false;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ((bool) ($user->is_admin ?? false)) {
            return true;
        }

        return $user->hasPermission('octram_entries.create');
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ((bool) ($user->is_admin ?? false)) {
            return true;
        }

        return $user->hasPermission('octram_entries.edit');
    }


    public static function form(Schema $schema): Schema
    {
        return OctramEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OctramEntriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOctramEntries::route('/'),
            'create' => CreateOctramEntry::route('/create'),
            'edit' => EditOctramEntry::route('/{record}/edit'),
        ];
    }
}