<?php

namespace App\Filament\Resources\Treasuries\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\Treasuries\TreasuryResource;
use App\Services\Documents\DocumentDeletionService;
use Filament\Resources\Pages\EditRecord;

class EditTreasury extends EditRecord
{
    protected static string $resource = TreasuryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProtectedDeleteAction::make()
                ->using(fn ($record): bool => app(DocumentDeletionService::class)->delete($record)),
        ];
    }
}
