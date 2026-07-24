<?php

namespace App\Filament\Resources\Treasuries;

use App\Filament\Resources\Treasuries\Pages\CreateTreasury;
use App\Filament\Resources\Treasuries\Pages\EditTreasury;
use App\Filament\Resources\Treasuries\Pages\ListTreasuries;
use App\Filament\Resources\Treasuries\Schemas\TreasuryForm;
use App\Filament\Resources\Treasuries\Tables\TreasuriesTable;
use App\Models\Treasury;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TreasuryResource extends Resource
{
    protected static ?string $model = Treasury::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'الخزائن';
    protected static ?string $modelLabel = 'خزينة';
    protected static ?string $pluralModelLabel = 'الخزائن';
    protected static string|\UnitEnum|null $navigationGroup = 'الخزينة';

    public static function form(Schema $schema): Schema { return TreasuryForm::configure($schema); }
    public static function table(Table $table): Table { return TreasuriesTable::configure($table); }
    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => ListTreasuries::route('/'),
            'create' => CreateTreasury::route('/create'),
            'edit' => EditTreasury::route('/{record}/edit'),
        ];
    }
}
