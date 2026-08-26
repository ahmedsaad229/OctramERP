<?php

namespace App\Filament\Resources\CustomerFollowUps;

use App\Filament\Resources\Core\BaseResource;

use App\Filament\Resources\CustomerFollowUps\Pages\CreateCustomerFollowUp;
use App\Filament\Resources\CustomerFollowUps\Pages\EditCustomerFollowUp;
use App\Filament\Resources\CustomerFollowUps\Pages\ListCustomerFollowUps;
use App\Filament\Resources\CustomerFollowUps\Schemas\CustomerFollowUpForm;
use App\Filament\Resources\CustomerFollowUps\Tables\CustomerFollowUpsTable;
use App\Models\CustomerFollowUp;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerFollowUpResource extends BaseResource
{
    protected static ?string $permissionKey = 'customer_follow_ups';
    protected static ?string $model = CustomerFollowUp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $recordTitleAttribute = 'follow_up_number';

    protected static ?string $navigationLabel = 'متابعة العملاء';

    protected static ?string $modelLabel = 'متابعة عميل';

    protected static ?string $pluralModelLabel = 'متابعات العملاء';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return CustomerFollowUpForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerFollowUpsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerFollowUps::route('/'),
            'create' => CreateCustomerFollowUp::route('/create'),
            'edit' => EditCustomerFollowUp::route('/{record}/edit'),
        ];
    }
}
