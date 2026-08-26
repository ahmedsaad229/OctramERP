<?php

namespace App\Filament\Resources\PurchaseRequests;

use App\Filament\Resources\Core\BaseResource;

use App\Filament\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\ListPurchaseRequests;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use App\Filament\Resources\PurchaseRequests\Tables\PurchaseRequestsTable;
use App\Models\PurchaseRequest;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchaseRequestResource extends BaseResource
{
    protected static ?string $permissionKey = 'purchase_requests';
    protected static ?string $model = PurchaseRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?string $navigationLabel = 'طلبات الشراء';

    protected static ?string $modelLabel = 'طلب شراء';

    protected static ?string $pluralModelLabel = 'طلبات الشراء';

    protected static string|\UnitEnum|null $navigationGroup = 'المشتريات';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return PurchaseRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseRequests::route('/'),
            'create' => CreatePurchaseRequest::route('/create'),
            'edit' => EditPurchaseRequest::route('/{record}/edit'),
        ];
    }
}
