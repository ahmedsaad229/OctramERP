<?php

namespace App\Filament\Resources\BankAccounts\Schemas;

use App\Models\Account;
use App\Models\Bank;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('code')
                    ->label('الكود')
                    ->readOnly()
                    ->dehydrated(),

                Select::make('bank_id')
                    ->label('البنك')
                    ->options(fn (): array => Bank::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('account_name')
                    ->label('اسم الحساب')
                    ->required()
                    ->maxLength(255),

                TextInput::make('branch_name')
                    ->label('الفرع')
                    ->maxLength(255),

                TextInput::make('account_number')
                    ->label('رقم الحساب')
                    ->maxLength(100),

                TextInput::make('iban')
                    ->label('IBAN')
                    ->maxLength(60),

                Select::make('currency')
                    ->label('العملة')
                    ->options([
                        'EGP' => 'جنيه مصري',
                        'USD' => 'دولار أمريكي',
                        'EUR' => 'يورو',
                        'SAR' => 'ريال سعودي',
                        'AED' => 'درهم إماراتي',
                    ])
                    ->default('EGP')
                    ->required(),

                TextInput::make('opening_balance')
                    ->label('الرصيد الافتتاحي')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Select::make('account_id')
                    ->label('الحساب بدليل الحسابات')
                    ->options(fn (): array => Account::query()
                        ->posting()
                        ->orderBy('code')
                        ->get()
                        ->mapWithKeys(
                            fn (Account $account): array => [
                                $account->id => $account->displayName(),
                            ]
                        )
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),

                Toggle::make('is_default')
                    ->label('الحساب الافتراضي'),

                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ]),

            Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}