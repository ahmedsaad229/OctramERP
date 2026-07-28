<?php

namespace App\Filament\Resources\GoodsReceiptVouchers;

use App\Filament\Resources\GoodsReceiptVouchers\Pages\CreateGoodsReceiptVoucher;
use App\Filament\Resources\GoodsReceiptVouchers\Pages\EditGoodsReceiptVoucher;
use App\Filament\Resources\GoodsReceiptVouchers\Pages\ListGoodsReceiptVouchers;
use App\Filament\Resources\OpeningStockVouchers\Schemas\OpeningStockVoucherForm;
use App\Filament\Resources\OpeningStockVouchers\Tables\OpeningStockVouchersTable;
use App\Models\GoodsReceiptVoucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GoodsReceiptVoucherResource extends Resource
{
    protected static ?string $model = GoodsReceiptVoucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?string $navigationLabel = 'إذن استلام';

    protected static ?string $modelLabel = 'إذن استلام';

    protected static ?string $pluralModelLabel = 'أذون الاستلام';

    protected static string|\UnitEnum|null $navigationGroup = 'المخازن';

    protected static ?string $navigationParentItem = 'عمليات المخزون';

    protected static ?int $navigationSort = 20;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

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
            'index' => ListGoodsReceiptVouchers::route('/'),
            'create' => CreateGoodsReceiptVoucher::route('/create'),
            'edit' => EditGoodsReceiptVoucher::route('/{record}/edit'),
        ];
    }
}
