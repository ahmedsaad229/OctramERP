<?php

namespace App\Filament\Resources\Accounts;

use App\Filament\Resources\Core\BaseResource;

use App\Filament\Resources\Accounts\Pages\CreateAccount;
use App\Filament\Resources\Accounts\Pages\EditAccount;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Accounts\Schemas\AccountForm;
use App\Filament\Resources\Accounts\Tables\AccountsTable;
use App\Models\Account;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AccountResource extends BaseResource
{
    protected static ?string $permissionKey = 'accounts';
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedQueueList;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'دليل الحسابات';

    protected static ?string $modelLabel = 'حساب';

    protected static ?string $pluralModelLabel = 'دليل الحسابات';

    protected static string|\UnitEnum|null $navigationGroup =
        'الحسابات العامة';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return AccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccounts::route('/'),
            'create' => CreateAccount::route('/create'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }
}
