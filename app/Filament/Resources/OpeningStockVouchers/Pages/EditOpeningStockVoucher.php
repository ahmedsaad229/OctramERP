<?php

namespace App\Filament\Resources\OpeningStockVouchers\Pages;

use App\Filament\Resources\OpeningStockVouchers\OpeningStockVoucherResource;
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
}
