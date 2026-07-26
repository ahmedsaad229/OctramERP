<?php

namespace App\Filament\Resources\SupplierPurchaseOrders\Pages;

use App\Filament\Resources\SupplierPurchaseOrders\SupplierPurchaseOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierPurchaseOrders extends ListRecords
{
    protected static string $resource = SupplierPurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
