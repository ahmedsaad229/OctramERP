<?php

namespace App\Filament\Resources\GoodsIssueVouchers;

use App\Filament\Resources\GoodsIssueVouchers\Pages\CreateGoodsIssueVoucher;
use App\Filament\Resources\GoodsIssueVouchers\Pages\EditGoodsIssueVoucher;
use App\Filament\Resources\GoodsIssueVouchers\Pages\ListGoodsIssueVouchers;
use App\Filament\Resources\OpeningStockVouchers\Schemas\OpeningStockVoucherForm;
use App\Filament\Resources\OpeningStockVouchers\Tables\OpeningStockVouchersTable;
use App\Models\GoodsIssueVoucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GoodsIssueVoucherResource extends Resource
{
    protected static ?string $model = GoodsIssueVoucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?string $navigationLabel = 'إذن صرف';

    protected static ?string $modelLabel = 'إذن صرف';

    protected static ?string $pluralModelLabel = 'أذون الصرف';

    protected static string|\UnitEnum|null $navigationGroup = 'المخازن';

    protected static ?string $navigationParentItem = 'عمليات المخزون';

    protected static ?int $navigationSort = 30;

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
            'index' => ListGoodsIssueVouchers::route('/'),
            'create' => CreateGoodsIssueVoucher::route('/create'),
            'edit' => EditGoodsIssueVoucher::route('/{record}/edit'),
        ];
    }
}
