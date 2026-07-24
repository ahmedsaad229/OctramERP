<?php

namespace App\Filament\Resources\StockBalances;

use App\Filament\Resources\StockBalances\Pages\ListStockBalances;
use App\Filament\Resources\StockBalances\Tables\StockBalancesTable;
use App\Models\StockBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockBalanceResource extends Resource
{
    protected static ?string $model = StockBalance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'أرصدة المخزون';

    protected static ?string $modelLabel = 'رصيد مخزون';

    protected static ?string $pluralModelLabel = 'أرصدة المخزون';

    protected static string|\UnitEnum|null $navigationGroup = 'عمليات المخزون';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return StockBalancesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockBalances::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
