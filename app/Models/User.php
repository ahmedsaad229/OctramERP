<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'email', 'mobile', 'job_title', 'password', 'is_active', 'is_admin', 'role_id', 'permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            $currentUser = auth()->user();

            if ($currentUser?->is($user)) {
                if ($user->isDirty('is_active') && ! $user->is_active) {
                    throw ValidationException::withMessages([
                        'is_active' => 'لا يمكنك إيقاف حسابك الحالي.',
                    ]);
                }

                if ($user->isDirty('is_admin') && ! $user->is_admin) {
                    throw ValidationException::withMessages([
                        'is_admin' => 'لا يمكنك إلغاء صفة مدير النظام عن حسابك الحالي.',
                    ]);
                }
            }

            if (
                $user->getOriginal('is_active')
                && $user->getOriginal('is_admin')
                && (
                    ($user->isDirty('is_active') && ! $user->is_active)
                    || ($user->isDirty('is_admin') && ! $user->is_admin)
                )
                && static::query()->where('is_active', true)->where('is_admin', true)->count() <= 1
            ) {
                throw ValidationException::withMessages([
                    'is_active' => 'لا يمكن إيقاف آخر مدير نظام نشط أو إلغاء صلاحية الإدارة عنه.',
                ]);
            }
        });

        static::deleting(function (User $user): void {
            if (auth()->user()?->is($user)) {
                throw ValidationException::withMessages([
                    'user' => 'لا يمكنك حذف حسابك الحالي.',
                ]);
            }

            if (
                $user->is_active
                && $user->is_admin
                && static::query()->where('is_active', true)->where('is_admin', true)->count() <= 1
            ) {
                throw ValidationException::withMessages([
                    'user' => 'لا يمكن حذف آخر مدير نظام نشط.',
                ]);
            }
        });
    }


    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->is_admin) {
            return true;
        }

        $permissions = $this->permissions;

        // Null means this user has no individual permission set yet,
        // so keep the role as a safe fallback.
        if ($permissions === null) {
            return $this->role?->hasPermission($permission) === true;
        }

        return in_array($permission, $permissions, true);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // A newly created in-memory model may not yet contain the database
        // default, so an absent attribute has the same meaning as the true default.
        return ($this->getAttribute('is_active') ?? true) === true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
            'permissions' => 'array',
        ];
    }
}
