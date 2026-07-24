<?php

namespace App\Filament\Resources\GoodsReceiptVouchers\Pages;

use App\Filament\Resources\GoodsReceiptVouchers\GoodsReceiptVoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGoodsReceiptVouchers extends ListRecords
{
    protected static string $resource = GoodsReceiptVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
