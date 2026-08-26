<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['permissions'] = $this->collectPermissions();

        return $data;
    }

    private function collectPermissions(): array
    {
        $state = $this->form->getRawState();

        return collect([
            ...($state['sales_permissions'] ?? []),
            ...($state['purchase_permissions'] ?? []),
            ...($state['inventory_permissions'] ?? []),
            ...($state['accounting_permissions'] ?? []),
            ...($state['report_permissions'] ?? []),
            ...($state['system_permissions'] ?? []),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
