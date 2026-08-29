<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['permissions'] = $this->collectPermissions();

        return $data;
    }

    private function collectPermissions(): array
    {
        $state = $this->form->getRawState();

        $permissions = [];

        foreach ([
            'sales_permissions',
            'purchase_permissions',
            'inventory_permissions',
            'accounting_permissions',
            'report_permissions',
            'system_permissions',
        ] as $field) {
            $permissions = array_merge(
                $permissions,
                $state[$field] ?? []
            );
        }

        return $this->normalizePermissions($permissions);
    }

    private function normalizePermissions(array $permissions): array
    {
        $permissions = array_values(array_unique($permissions));

        foreach ($permissions as $permission) {
            if (! str_ends_with($permission, '.view')) {
                continue;
            }

            $module = substr($permission, 0, -5);

            if ($module === 'dashboard') {
                continue;
            }

            $permissions[] = "{$module}.print";
            $permissions[] = "{$module}.export";
        }

        return array_values(array_unique($permissions));
    }
}
