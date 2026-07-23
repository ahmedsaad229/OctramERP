<?php

namespace App\Filament\Resources\OpeningStockVouchers\Pages;

use App\Filament\Resources\OpeningStockVouchers\OpeningStockVoucherResource;
use App\Services\Inventory\OpeningStockService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOpeningStockVoucher extends EditRecord
{
    protected static string $resource = OpeningStockVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(OpeningStockService::class)->post($this->record);
    }
}