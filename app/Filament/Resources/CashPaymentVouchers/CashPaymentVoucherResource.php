<?php

namespace App\Filament\Resources\CashPaymentVouchers;

use App\Filament\Resources\Core\BaseResource;

use App\Filament\Resources\CashPaymentVouchers\Pages\CreateCashPaymentVoucher;
use App\Filament\Resources\CashPaymentVouchers\Pages\EditCashPaymentVoucher;
use App\Filament\Resources\CashPaymentVouchers\Pages\ListCashPaymentVouchers;
use App\Filament\Resources\CashPaymentVouchers\Schemas\CashPaymentVoucherForm;
use App\Filament\Resources\CashPaymentVouchers\Tables\CashPaymentVouchersTable;
use App\Models\SupplierPaymentVoucher;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashPaymentVoucherResource extends BaseResource
{
    protected static ?string $permissionKey = 'cash_payment_vouchers';
    protected static ?string $model = SupplierPaymentVoucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $recordTitleAttribute = 'document_number';

    protected static ?string $navigationLabel = 'سندات صرف النقدية';

    protected static ?string $modelLabel = 'سند صرف نقدية';

    protected static ?string $pluralModelLabel = 'سندات صرف النقدية';

    protected static string|\UnitEnum|null $navigationGroup = 'الخزينة';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return CashPaymentVoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashPaymentVouchersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashPaymentVouchers::route('/'),
            'create' => CreateCashPaymentVoucher::route('/create'),
            'edit' => EditCashPaymentVoucher::route('/{record}/edit'),
        ];
    }
}
