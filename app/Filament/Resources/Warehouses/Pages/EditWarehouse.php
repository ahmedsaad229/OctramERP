<?php

namespace App\Filament\Resources\Warehouses\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\Warehouses\WarehouseResource;
use App\Services\Documents\DocumentDeletionService;
use Filament\Resources\Pages\EditRecord;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProtectedDeleteAction::make()
                ->using(fn ($record): bool => app(DocumentDeletionService::class)->delete($record)),
        ];
    }
}
