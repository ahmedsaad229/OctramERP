<?php

namespace App\Filament\Resources\OpeningStocks\Pages;

use App\Filament\Resources\OpeningStocks\OpeningStockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOpeningStock extends EditRecord
{
    protected static string $resource = OpeningStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
