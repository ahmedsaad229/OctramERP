<?php

namespace App\Filament\Resources\CashAdvances\Tables;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Models\CashAdvance;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashAdvancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('advance_date', 'desc')
            ->columns([

                TextColumn::make('document_number')
                    ->label('رقم العهدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('advance_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('recipient_name')
                    ->label('المستلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purpose')
                    ->label('الغرض')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('amount')
                    ->label('مبلغ العهدة')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('total_spent')
                    ->label('المصروف')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('total_returned')
                    ->label('المرتجع')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->money('EGP')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(
                        fn ($state): string =>
                            CashAdvance::statusOptions()[$state] ?? $state
                    )
                    ->badge()
                    ->color(
                        fn ($state): string => match ($state) {
                            CashAdvance::STATUS_SETTLED => 'success',
                            CashAdvance::STATUS_PARTIAL => 'warning',
                            default => 'gray',
                        }
                    ),

                TextColumn::make('due_date')
                    ->label('موعد التسوية')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(CashAdvance::statusOptions()),
            ])
            ->recordActions([
                EditAction::make(),

                ProtectedDeleteAction::make()
                    ->iconButton()
                    ->tooltip('حذف'),
            ]);
    }
}
