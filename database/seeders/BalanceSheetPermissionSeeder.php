<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class BalanceSheetPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $manage = ['balance_sheet.view', 'balance_sheet.print', 'balance_sheet.export'];

        foreach (['مدير عام', 'الحسابات'] as $name) {
            $role = Role::query()->where('name', $name)->first();

            if (! $role) {
                continue;
            }

            $role->update([
                'permissions' => array_values(array_unique([
                    ...(array) ($role->permissions ?? []),
                    ...$manage,
                ])),
            ]);
        }

        $viewer = Role::query()->where('name', 'مشاهدة فقط')->first();

        if ($viewer) {
            $viewer->update([
                'permissions' => array_values(array_unique([
                    ...(array) ($viewer->permissions ?? []),
                    'balance_sheet.view',
                    'balance_sheet.print',
                ])),
            ]);
        }
    }
}
