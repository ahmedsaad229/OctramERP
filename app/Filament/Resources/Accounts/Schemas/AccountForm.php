<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Models\Account;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الحساب')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('كود الحساب')
                                    ->required()
                                    ->maxLength(30)
                                    ->unique(ignoreRecord: true),

                                TextInput::make('name')
                                    ->label('اسم الحساب')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('name_en')
                                    ->label('الاسم بالإنجليزية')
                                    ->maxLength(255),

                                Select::make('parent_id')
                                    ->label('الحساب الأب')
                                    ->options(fn (): array => Account::query()
                                        ->where('is_group', true)
                                        ->orderBy('code')
                                        ->get()
                                        ->mapWithKeys(
                                            fn (Account $account): array => [
                                                $account->getKey() =>
                                                    $account->displayName(),
                                            ]
                                        )
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),

                                Select::make('account_type')
                                    ->label('تصنيف الحساب')
                                    ->options(Account::typeOptions())
                                    ->required()
                                    ->native(false),

                                Select::make('normal_balance')
                                    ->label('طبيعة الحساب')
                                    ->options(Account::balanceOptions())
                                    ->required()
                                    ->native(false),

                                TextInput::make('sort_order')
                                    ->label('ترتيب العرض')
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ]),

                Section::make('إعدادات الحساب')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_group')
                                    ->label('حساب رئيسي')
                                    ->helperText(
                                        'الحساب الرئيسي يحتوي على حسابات فرعية ولا يقبل قيودًا مباشرة.'
                                    )
                                    ->live()
                                    ->default(false),

                                Toggle::make('allow_posting')
                                    ->label('السماح بالقيد المباشر')
                                    ->default(true)
                                    ->disabled(
                                        fn ($get): bool =>
                                            (bool) $get('is_group')
                                    )
                                    ->dehydrated(),

                                Toggle::make('requires_cost_center')
                                    ->label('يتطلب مركز تكلفة')
                                    ->default(false),

                                Toggle::make('active')
                                    ->label('نشط')
                                    ->default(true),
                            ]),

                        Textarea::make('description')
                            ->label('الوصف')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
