<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /*
         * System administrators do not depend on individual permissions.
         * Keep their stored permissions untouched.
         */
        if (! ($data['is_admin'] ?? false)) {
            $data['permissions'] = $this->collectPermissions();
        }

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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(function (DeleteAction $action): void {
                    try {
                        $action->getRecord()->delete();
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('تعذر حذف المستخدم')
                            ->body(collect($exception->errors())->flatten()->first())
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
