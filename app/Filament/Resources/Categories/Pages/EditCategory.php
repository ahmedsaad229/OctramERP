<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\Categories\CategoryResource;
use App\Services\Documents\DocumentDeletionService;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProtectedDeleteAction::make()
                ->using(fn ($record): bool => app(DocumentDeletionService::class)->delete($record)),
        ];
    }
}
