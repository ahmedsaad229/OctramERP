<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\Customers\CustomerResource;
use App\Services\Documents\DocumentDeletionService;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProtectedDeleteAction::make()
                ->using(fn ($record): bool => app(DocumentDeletionService::class)->delete($record)),
        ];
    }
}
