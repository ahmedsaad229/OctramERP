<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class CashFlowStatementPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $manage = [
            'cash_flow_statement.view',
            'cash_flow_statement.print',
            'cash_flow_statement.export',
        ];

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
                    'cash_flow_statement.view',
                    'cash_flow_statement.print',
                ])),
            ]);
        }
    }
}
