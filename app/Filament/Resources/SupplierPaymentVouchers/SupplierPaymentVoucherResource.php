<?php

namespace App\Filament\Resources\SupplierPaymentVouchers;

use App\Filament\Resources\SupplierPaymentVouchers\Pages\CreateSupplierPaymentVoucher;
use App\Filament\Resources\SupplierPaymentVouchers\Pages\EditSupplierPaymentVoucher;
use App\Filament\Resources\SupplierPaymentVouchers\Pages\ListSupplierPaymentVouchers;
use App\Filament\Resources\SupplierPaymentVouchers\Schemas\SupplierPaymentVoucherForm;
use App\Filament\Resources\SupplierPaymentVouchers\Tables\SupplierPaymentVouchersTable;
use App\Models\SupplierPaymentVoucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupplierPaymentVoucherResource extends Resource
{
    protected static ?string $model = SupplierPaymentVoucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $recordTitleAttribute = 'document_number';

    protected static ?string $navigationLabel = 'سندات صرف الموردين';

    protected static ?string $modelLabel = 'سند صرف مورد';

    protected static ?string $pluralModelLabel = 'سندات صرف الموردين';

    protected static string|\UnitEnum|null $navigationGroup = 'الحسابات المالية';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SupplierPaymentVoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierPaymentVouchersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupplierPaymentVouchers::route('/'),
            'create' => CreateSupplierPaymentVoucher::route('/create'),
            'edit' => EditSupplierPaymentVoucher::route('/{record}/edit'),
        ];
    }
}
