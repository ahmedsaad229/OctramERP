<?php

namespace App\Filament\Resources\ReceiptVouchers;

use App\Filament\Resources\ReceiptVouchers\Pages\CreateReceiptVoucher;
use App\Filament\Resources\ReceiptVouchers\Pages\EditReceiptVoucher;
use App\Filament\Resources\ReceiptVouchers\Pages\ListReceiptVouchers;
use App\Filament\Resources\ReceiptVouchers\Schemas\ReceiptVoucherForm;
use App\Filament\Resources\ReceiptVouchers\Tables\ReceiptVouchersTable;
use App\Models\ReceiptVoucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReceiptVoucherResource extends Resource
{
    protected static ?string $model = ReceiptVoucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'document_number';

    protected static ?string $navigationLabel = 'سندات القبض';

    protected static ?string $modelLabel = 'سند قبض';

    protected static ?string $pluralModelLabel = 'سندات القبض';

    protected static string|\UnitEnum|null $navigationGroup = 'الحسابات المالية';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ReceiptVoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReceiptVouchersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReceiptVouchers::route('/'),
            'create' => CreateReceiptVoucher::route('/create'),
            'edit' => EditReceiptVoucher::route('/{record}/edit'),
        ];
    }
}
