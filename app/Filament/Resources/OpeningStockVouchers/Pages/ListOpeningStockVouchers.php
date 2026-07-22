<?php

namespace App\Filament\Resources\OpeningStockVouchers\Pages;

use App\Filament\Resources\OpeningStockVouchers\OpeningStockVoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpeningStockVouchers extends ListRecords
{
    protected static string $resource = OpeningStockVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
