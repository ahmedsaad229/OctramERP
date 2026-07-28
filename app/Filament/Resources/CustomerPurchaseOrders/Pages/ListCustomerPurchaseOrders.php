<?php

namespace App\Filament\Resources\CustomerPurchaseOrders\Pages;

use App\Filament\Resources\CustomerPurchaseOrders\CustomerPurchaseOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerPurchaseOrders extends ListRecords
{
    protected static string $resource = CustomerPurchaseOrderResource::class;

    protected static ?string $title = 'أوامر توريد العملاء';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('إضافة أمر توريد عميل')];
    }
}
