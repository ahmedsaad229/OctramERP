<?php

namespace App\Filament\Resources\Core;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseResource extends Resource
{
    protected static ?string $permissionKey = null;

    protected static function permissionModule(): string
    {
        if (filled(static::$permissionKey)) {
            return static::$permissionKey;
        }

        return Str::of(class_basename(static::class))
            ->beforeLast('Resource')
            ->snake()
            ->plural()
            ->toString();
    }

    protected static function allowed(string $action): bool
    {
        return auth()->user()?->hasPermission(static::permissionModule().'.'.$action) === true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::allowed('view');
    }

    public static function canAccess(): bool
    {
        return static::allowed('view');
    }

    public static function canViewAny(): bool
    {
        return static::allowed('view');
    }

    public static function canView(Model $record): bool
    {
        return static::allowed('view');
    }

    public static function canCreate(): bool
    {
        return static::allowed('create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::allowed('edit');
    }

    public static function canDelete(Model $record): bool
    {
        return static::allowed('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::allowed('delete');
    }
}
