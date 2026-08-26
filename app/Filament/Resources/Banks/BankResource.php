<?php
namespace App\Filament\Resources\Banks;

use App\Filament\Resources\Banks\Pages\CreateBank;
use App\Filament\Resources\Banks\Pages\EditBank;
use App\Filament\Resources\Banks\Pages\ListBanks;
use App\Filament\Resources\Banks\RelationManagers\AccountsRelationManager;
use App\Filament\Resources\Banks\Schemas\BankForm;
use App\Filament\Resources\Banks\Tables\BanksTable;
use App\Filament\Resources\Core\BaseResource;
use App\Models\Bank;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BankResource extends BaseResource
{
    protected static ?string $model = Bank::class;
    protected static ?string $permissionKey = 'banks';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;
    protected static ?string $navigationLabel = 'البنوك';
    protected static ?string $modelLabel = 'بنك';
    protected static ?string $pluralModelLabel = 'البنوك';
    protected static string|\UnitEnum|null $navigationGroup = 'الحسابات العامة';
    protected static ?int $navigationSort = 10;
    public static function form(Schema $schema): Schema { return BankForm::configure($schema); }
    public static function table(Table $table): Table { return BanksTable::configure($table); }
    public static function getRelations(): array { return [AccountsRelationManager::class]; }
    public static function getPages(): array { return ['index'=>ListBanks::route('/'),'create'=>CreateBank::route('/create'),'edit'=>EditBank::route('/{record}/edit')]; }
}
