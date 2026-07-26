<?php

namespace App\Filament\Resources\SupplierPurchaseOrders;

use App\Filament\Resources\SupplierPurchaseOrders\Pages\CreateSupplierPurchaseOrder;
use App\Filament\Resources\SupplierPurchaseOrders\Pages\EditSupplierPurchaseOrder;
use App\Filament\Resources\SupplierPurchaseOrders\Pages\ListSupplierPurchaseOrders;
use App\Filament\Resources\SupplierPurchaseOrders\Schemas\SupplierPurchaseOrderForm;
use App\Filament\Resources\SupplierPurchaseOrders\Tables\SupplierPurchaseOrdersTable;
use App\Models\SupplierPurchaseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupplierPurchaseOrderResource extends Resource
{
    protected static ?string $model = SupplierPurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?string $navigationLabel = 'أوامر التوريد';

    protected static ?string $modelLabel = 'أمر توريد';

    protected static ?string $pluralModelLabel = 'أوامر التوريد';

    protected static string|\UnitEnum|null $navigationGroup = 'المشتريات';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return SupplierPurchaseOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierPurchaseOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupplierPurchaseOrders::route('/'),
            'create' => CreateSupplierPurchaseOrder::route('/create'),
            'edit' => EditSupplierPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
