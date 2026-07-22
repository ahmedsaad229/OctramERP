<?php

namespace App\Filament\Resources\OpeningStockVouchers;

use App\Filament\Resources\OpeningStockVouchers\Pages\CreateOpeningStockVoucher;
use App\Filament\Resources\OpeningStockVouchers\Pages\EditOpeningStockVoucher;
use App\Filament\Resources\OpeningStockVouchers\Pages\ListOpeningStockVouchers;
use App\Filament\Resources\OpeningStockVouchers\Schemas\OpeningStockVoucherForm;
use App\Filament\Resources\OpeningStockVouchers\Tables\OpeningStockVouchersTable;
use App\Models\OpeningStockVoucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OpeningStockVoucherResource extends Resource
{
    protected static ?string $model = OpeningStockVoucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?string $navigationLabel = 'رصيد أول المدة';

    protected static ?string $modelLabel = 'مستند أول المدة';

    protected static ?string $pluralModelLabel = 'مستندات أول المدة';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المخزون';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return OpeningStockVoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpeningStockVouchersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOpeningStockVouchers::route('/'),
            'create' => CreateOpeningStockVoucher::route('/create'),
            'edit' => EditOpeningStockVoucher::route('/{record}/edit'),
        ];
    }
}