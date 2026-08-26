<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allWithoutDelete = collect(array_keys(PermissionRegistry::permissionOptions()))
            ->reject(fn (string $permission): bool => Str::endsWith($permission, '.delete'))
            ->values()
            ->all();

        Role::query()->updateOrCreate(
            ['name' => 'مدير عام'],
            [
                'description' => 'كامل الصلاحيات التشغيلية بدون حذف افتراضيًا.',
                'permissions' => $allWithoutDelete,
                'is_system' => true,
            ],
        );

        foreach (['المشتريات', 'المبيعات', 'المخازن', 'الحسابات', 'مشاهدة فقط'] as $name) {
            Role::query()->firstOrCreate(
                ['name' => $name],
                ['permissions' => [], 'is_system' => true],
            );
        }

        // أمان افتراضي: إزالة كل صلاحيات الحذف من جميع الأدوار الحالية.
        // يمكن لمدير النظام إعادة تفعيل حذف مستند محدد لاحقًا من شاشة الدور.
        Role::query()->get()->each(function (Role $role): void {
            $permissions = collect($role->permissions ?? [])
                ->reject(fn (string $permission): bool => Str::endsWith($permission, '.delete'))
                ->values()
                ->all();

            $role->forceFill(['permissions' => $permissions])->save();
        });
    }
}
