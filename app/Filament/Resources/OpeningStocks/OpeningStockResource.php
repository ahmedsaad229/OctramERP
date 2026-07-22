<?php

namespace App\Filament\Resources\OpeningStocks;

use App\Filament\Resources\OpeningStocks\Pages\CreateOpeningStock;
use App\Filament\Resources\OpeningStocks\Pages\EditOpeningStock;
use App\Filament\Resources\OpeningStocks\Pages\ListOpeningStocks;
use App\Filament\Resources\OpeningStocks\Schemas\OpeningStockForm;
use App\Filament\Resources\OpeningStocks\Tables\OpeningStocksTable;
use App\Models\OpeningStock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OpeningStockResource extends Resource
{
    protected static ?string $model = OpeningStock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return OpeningStockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpeningStocksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOpeningStocks::route('/'),
            'create' => CreateOpeningStock::route('/create'),
            'edit' => EditOpeningStock::route('/{record}/edit'),
        ];
    }
}
