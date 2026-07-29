<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_and_open_user_resource(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin);

        $this->assertTrue(UserResource::shouldRegisterNavigation());
        $this->get(UserResource::getUrl())->assertOk();
    }

    public function test_non_admin_cannot_see_or_open_user_resource(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $this->actingAs($user);

        $this->assertFalse(UserResource::shouldRegisterNavigation());
        $this->get(UserResource::getUrl())->assertForbidden();
    }

    public function test_inactive_user_cannot_access_filament_panel(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->assertFalse($user->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_admin_can_create_a_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'مستخدم جديد',
                'email' => 'new-user@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => true,
                'is_admin' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();

        $this->assertSame('مستخدم جديد', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_admin);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_editing_user_without_password_keeps_existing_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $originalPassword = $user->password;
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => 'الاسم المعدل',
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => true,
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('الاسم المعدل', $user->refresh()->name);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_admin_can_change_user_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'is_active' => true,
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        try {
            $admin->delete();
            $this->fail('The current user deleted their own account.');
        } catch (ValidationException $exception) {
            $this->assertSame('لا يمكنك حذف حسابك الحالي.', $exception->errors()['user'][0]);
        }

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_last_active_admin_cannot_be_deleted_or_deactivated(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $operator = User::factory()->create(['is_admin' => false, 'is_active' => true]);
        $this->actingAs($operator);

        try {
            $admin->delete();
            $this->fail('The last active admin was deleted.');
        } catch (ValidationException $exception) {
            $this->assertSame('لا يمكن حذف آخر مدير نظام نشط.', $exception->errors()['user'][0]);
        }

        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        try {
            $admin->update(['is_active' => false]);
            $this->fail('The last active admin was deactivated.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'لا يمكن إيقاف آخر مدير نظام نشط أو إلغاء صلاحية الإدارة عنه.',
                $exception->errors()['is_active'][0],
            );
        }

        $this->assertTrue($admin->refresh()->is_active);
    }
}
