<?php

namespace App\Filament\Resources\Treasuries\Pages;

use App\Filament\Resources\Treasuries\TreasuryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTreasury extends EditRecord
{
    protected static string $resource = TreasuryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
