<?php

namespace App\Filament\Resources\BankAccounts\Tables;

use App\Filament\Actions\ProtectedDeleteAction;

use App\Models\BankAccount;
use App\Services\BankTransactionService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bank.name')
                    ->label('البنك')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('account_name')
                    ->label('اسم الحساب')
                    ->searchable(),

                TextColumn::make('account_number')
                    ->label('رقم الحساب')
                    ->placeholder('—'),

                TextColumn::make('currency')
                    ->label('العملة')
                    ->badge(),

                TextColumn::make('current_balance')
                    ->label('الرصيد الحالي')
                    ->state(
                        fn (BankAccount $record): float => app(BankTransactionService::class)
                            ->balance($record)
                    )
                    ->money(
                        fn (BankAccount $record): string => $record->currency ?: 'EGP'
                    )
                    ->sortable(false),

                IconColumn::make('is_default')
                    ->label('افتراضي')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                ProtectedDeleteAction::make()
                    ->iconButton()
                    ->tooltip('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}