<?php

namespace App\Filament\Resources\OpeningStocks\Pages;

use App\Filament\Resources\OpeningStocks\OpeningStockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpeningStocks extends ListRecords
{
    protected static string $resource = OpeningStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
