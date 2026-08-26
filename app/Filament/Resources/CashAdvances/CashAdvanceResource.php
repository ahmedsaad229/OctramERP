<?php

namespace App\Filament\Resources\CashAdvances;

use App\Filament\Resources\CashAdvances\Pages\CreateCashAdvance;
use App\Filament\Resources\CashAdvances\Pages\EditCashAdvance;
use App\Filament\Resources\CashAdvances\Pages\ListCashAdvances;
use App\Filament\Resources\CashAdvances\Schemas\CashAdvanceForm;
use App\Filament\Resources\CashAdvances\Tables\CashAdvancesTable;
use App\Models\CashAdvance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CashAdvanceResource extends Resource
{
    protected static ?string $model = CashAdvance::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'العهد النقدية';

    protected static ?string $modelLabel = 'عهدة نقدية';

    protected static ?string $pluralModelLabel = 'العهد النقدية';

    protected static string|\UnitEnum|null $navigationGroup = 'الحسابات العامة';

    protected static ?int $navigationSort = 18;

    public static function form(Schema $schema): Schema
    {
        return CashAdvanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashAdvancesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashAdvances::route('/'),
            'create' => CreateCashAdvance::route('/create'),
            'edit' => EditCashAdvance::route('/{record}/edit'),
        ];
    }
}
