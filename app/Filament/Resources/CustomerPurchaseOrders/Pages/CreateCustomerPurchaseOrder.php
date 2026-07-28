<?php

namespace App\Filament\Resources\CustomerPurchaseOrders\Pages;

use App\Filament\Resources\CustomerPurchaseOrders\CustomerPurchaseOrderResource;
use App\Services\CustomerPurchaseOrderService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCustomerPurchaseOrder extends CreateRecord
{
    protected static string $resource = CustomerPurchaseOrderResource::class;

    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'إضافة أمر توريد عميل';

    protected function handleRecordCreation(array $data): Model
    {
        return app(CustomerPurchaseOrderService::class)->create($data);
    }
}
