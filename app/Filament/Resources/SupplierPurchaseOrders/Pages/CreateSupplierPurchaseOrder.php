<?php

namespace App\Filament\Resources\SupplierPurchaseOrders\Pages;

use App\Filament\Resources\SupplierPurchaseOrders\SupplierPurchaseOrderResource;
use App\Services\SupplierPurchaseOrderService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSupplierPurchaseOrder extends CreateRecord
{
    protected static string $resource = SupplierPurchaseOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SupplierPurchaseOrderService::class)->create($data);
    }
}
