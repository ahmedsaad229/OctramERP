<?php

namespace App\Filament\Resources\OpeningStockVouchers\Pages;

use App\Filament\Resources\OpeningStockVouchers\OpeningStockVoucherResource;
use App\Services\Inventory\OpeningStockService;
use Filament\Resources\Pages\CreateRecord;

class CreateOpeningStockVoucher extends CreateRecord
{
    protected static string $resource = OpeningStockVoucherResource::class;

    protected function afterCreate(): void
    {
        app(OpeningStockService::class)->post($this->record);
    }
}