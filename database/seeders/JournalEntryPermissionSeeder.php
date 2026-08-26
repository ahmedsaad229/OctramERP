<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class JournalEntryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $manage = [
            'journal_entries.view',
            'journal_entries.create',
            'journal_entries.edit',
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
                    'journal_entries.view',
                ])),
            ]);
        }
    }
}
