<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Core\BaseResource;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RoleResource extends BaseResource
{
    protected static ?string $model = Role::class;
    protected static ?string $permissionKey = 'roles';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static ?string $navigationLabel = 'الأدوار والصلاحيات';
    protected static ?string $modelLabel = 'دور';
    protected static ?string $pluralModelLabel = 'الأدوار والصلاحيات';
    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';
    protected static ?int $navigationSort = 20;

    private static function isAdmin(): bool { return auth()->user()?->is_admin === true; }
    public static function shouldRegisterNavigation(): bool { return false; }
    public static function canAccess(): bool { return static::isAdmin(); }
    public static function canViewAny(): bool { return static::isAdmin(); }
    public static function canCreate(): bool { return static::isAdmin(); }
    public static function canEdit(Model $record): bool { return static::isAdmin(); }
    public static function canDelete(Model $record): bool { return static::isAdmin() && ! $record->is_system && $record->users()->doesntExist(); }

    public static function form(Schema $schema): Schema { return RoleForm::configure($schema); }
    public static function table(Table $table): Table { return RolesTable::configure($table); }
    public static function getPages(): array { return [
        'index' => ListRoles::route('/'),
        'create' => CreateRole::route('/create'),
        'edit' => EditRole::route('/{record}/edit'),
    ]; }
}
