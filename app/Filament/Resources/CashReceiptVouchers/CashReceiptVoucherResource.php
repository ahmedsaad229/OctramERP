<?php

namespace App\Filament\Resources\CashReceiptVouchers;

use App\Filament\Resources\Core\BaseResource;

use App\Filament\Resources\CashReceiptVouchers\Pages\CreateCashReceiptVoucher;
use App\Filament\Resources\CashReceiptVouchers\Pages\EditCashReceiptVoucher;
use App\Filament\Resources\CashReceiptVouchers\Pages\ListCashReceiptVouchers;
use App\Filament\Resources\CashReceiptVouchers\Pages\ViewCashReceiptVoucher;
use App\Filament\Resources\CashReceiptVouchers\Schemas\CashReceiptVoucherForm;
use App\Filament\Resources\CashReceiptVouchers\Tables\CashReceiptVouchersTable;
use App\Models\ReceiptVoucher;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashReceiptVoucherResource extends BaseResource
{
    protected static ?string $permissionKey = 'cash_receipt_vouchers';
    protected static ?string $model = ReceiptVoucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $recordTitleAttribute = 'document_number';

    protected static ?string $navigationLabel = 'سندات استلام النقدية';

    protected static ?string $modelLabel = 'سند استلام نقدية';

    protected static ?string $pluralModelLabel = 'سندات استلام النقدية';

    protected static string|\UnitEnum|null $navigationGroup = 'الخزينة';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return CashReceiptVoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashReceiptVouchersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashReceiptVouchers::route('/'),
            'create' => CreateCashReceiptVoucher::route('/create'),
            'view' => ViewCashReceiptVoucher::route('/{record}'),
            'edit' => EditCashReceiptVoucher::route('/{record}/edit'),
        ];
    }
}
