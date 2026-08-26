<?php

namespace App\Filament\Resources\CustomerPurchaseOrders;

use App\Filament\Resources\Core\BaseResource;
use App\Filament\Resources\CustomerPurchaseOrders\Pages\CreateCustomerPurchaseOrder;
use App\Filament\Resources\CustomerPurchaseOrders\Pages\EditCustomerPurchaseOrder;
use App\Filament\Resources\CustomerPurchaseOrders\Pages\ListCustomerPurchaseOrders;
use App\Filament\Resources\CustomerPurchaseOrders\Schemas\CustomerPurchaseOrderForm;
use App\Filament\Resources\CustomerPurchaseOrders\Tables\CustomerPurchaseOrdersTable;
use App\Models\CustomerPurchaseOrder;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CustomerPurchaseOrderResource extends BaseResource
{
    protected static string $permissionPrefix = 'customer_purchase_orders';

    protected static ?string $model = CustomerPurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'أوامر توريد العملاء';

    protected static ?string $modelLabel = 'أمر توريد عميل';

    protected static ?string $pluralModelLabel = 'أوامر توريد العملاء';

    protected static string|\UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'document_number';

    public static function form(Schema $schema): Schema
    {
        return CustomerPurchaseOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerPurchaseOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListCustomerPurchaseOrders::route('/'), 'create' => CreateCustomerPurchaseOrder::route('/create'), 'edit' => EditCustomerPurchaseOrder::route('/{record}/edit')];
    }
}
