<?php

namespace App\Filament\Resources\Banks\RelationManagers;

use App\Models\Account;
use App\Services\BankTransactionService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'accounts';

    protected static ?string $title = 'الحسابات البنكية';

    protected static ?string $modelLabel = 'حساب بنكي';

    protected static ?string $pluralModelLabel = 'الحسابات البنكية';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('code')
                    ->label('الكود')
                    ->readOnly()
                    ->dehydrated(),

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
                        ->mapWithKeys(fn (Account $account): array => [
                            $account->id => $account->displayName(),
                        ])
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('account_name')
            ->columns([
                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('account_name')
                    ->label('اسم الحساب')
                    ->searchable(),

                TextColumn::make('branch_name')
                    ->label('الفرع')
                    ->placeholder('—'),

                TextColumn::make('account_number')
                    ->label('رقم الحساب')
                    ->placeholder('—'),

                TextColumn::make('currency')
                    ->label('العملة')
                    ->badge(),

                TextColumn::make('current_balance')
                    ->label('الرصيد الحالي')
                    ->state(fn ($record): float => app(BankTransactionService::class)->balance($record))
                    ->money(fn ($record): string => $record->currency)
                    ->sortable(false),

                IconColumn::make('is_default')
                    ->label('افتراضي')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة حساب بنكي'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
