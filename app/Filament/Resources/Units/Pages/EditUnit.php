<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\Units\UnitResource;
use App\Services\Documents\DocumentDeletionService;
use Filament\Resources\Pages\EditRecord;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProtectedDeleteAction::make()
                ->using(fn ($record): bool => app(DocumentDeletionService::class)->delete($record)),
        ];
    }
}
