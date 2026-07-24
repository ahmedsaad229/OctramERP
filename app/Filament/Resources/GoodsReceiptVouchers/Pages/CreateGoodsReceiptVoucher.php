<?php

namespace App\Filament\Resources\GoodsReceiptVouchers\Pages;

use App\Filament\Resources\GoodsReceiptVouchers\GoodsReceiptVoucherResource;
use App\Services\Inventory\GoodsReceiptService;
use Filament\Resources\Pages\CreateRecord;

class CreateGoodsReceiptVoucher extends CreateRecord
{
    protected static string $resource = GoodsReceiptVoucherResource::class;

    protected function afterCreate(): void
    {
        app(GoodsReceiptService::class)->post($this->record);
    }
}
