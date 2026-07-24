<?php

namespace App\Filament\Resources\GoodsReceiptVouchers\Pages;

use App\Filament\Resources\GoodsReceiptVouchers\GoodsReceiptVoucherResource;
use App\Services\Inventory\GoodsReceiptService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGoodsReceiptVoucher extends EditRecord
{
    protected static string $resource = GoodsReceiptVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(GoodsReceiptService::class)->post($this->record);
    }
}
