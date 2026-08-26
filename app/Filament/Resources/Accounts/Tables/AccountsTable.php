<?php

namespace App\Filament\Resources\Accounts\Tables;

use App\Models\Account;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('تم نسخ كود الحساب'),

                TextColumn::make('name')
                    ->label('اسم الحساب')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(
                        fn (Account $record): string =>
                            str_repeat('— ', max(0, $record->level - 1))
                            .$record->name
                    ),

                TextColumn::make('parent.name')
                    ->label('الحساب الأب')
                    ->placeholder('حساب رئيسي')
                    ->toggleable(),

                TextColumn::make('account_type')
                    ->label('التصنيف')
                    ->formatStateUsing(
                        fn (string $state): string =>
                            Account::typeOptions()[$state] ?? $state
                    )
                    ->badge(),

                TextColumn::make('normal_balance')
                    ->label('الطبيعة')
                    ->formatStateUsing(
                        fn (string $state): string =>
                            Account::balanceOptions()[$state] ?? $state
                    )
                    ->badge()
                    ->color(
                        fn (string $state): string =>
                            $state === Account::BALANCE_DEBIT
                                ? 'info'
                                : 'success'
                    ),

                TextColumn::make('level')
                    ->label('المستوى')
                    ->badge()
                    ->toggleable(),

                IconColumn::make('is_group')
                    ->label('رئيسي')
                    ->boolean(),

                IconColumn::make('allow_posting')
                    ->label('يقبل قيودًا')
                    ->boolean(),

                IconColumn::make('requires_cost_center')
                    ->label('مركز تكلفة')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->label('تصنيف الحساب')
                    ->options(Account::typeOptions()),

                SelectFilter::make('normal_balance')
                    ->label('طبيعة الحساب')
                    ->options(Account::balanceOptions()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
            ]);
    }
}
