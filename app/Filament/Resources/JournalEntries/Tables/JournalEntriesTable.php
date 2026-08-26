<?php

namespace App\Filament\Resources\JournalEntries\Tables;

use App\Models\JournalEntry;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JournalEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['lines', 'creator'])
                ->orderByDesc('entry_date')
                ->orderByDesc('id'))
            ->columns([
                TextColumn::make('document_number')
                    ->label('رقم القيد')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('entry_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('entry_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => JournalEntry::typeOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => $state === JournalEntry::TYPE_MANUAL ? 'warning' : 'info'),

                TextColumn::make('description')
                    ->label('البيان')
                    ->searchable()
                    ->limit(55)
                    ->wrap(),

                TextColumn::make('source_type')
                    ->label('المصدر')
                    ->formatStateUsing(fn (?string $state, JournalEntry $record): string => $record->isManual()
                        ? 'قيد يدوي'
                        : class_basename((string) $state))
                    ->badge()
                    ->toggleable(),

                TextColumn::make('total_debit')
                    ->label('إجمالي المدين')
                    ->state(fn (JournalEntry $record): float => $record->totalDebit())
                    ->money('EGP')
                    ->alignCenter(),

                TextColumn::make('total_credit')
                    ->label('إجمالي الدائن')
                    ->state(fn (JournalEntry $record): float => $record->totalCredit())
                    ->money('EGP')
                    ->alignCenter(),

                TextColumn::make('creator.name')
                    ->label('أنشأه')
                    ->placeholder('النظام')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('entry_type')
                    ->label('نوع القيد')
                    ->options(JournalEntry::typeOptions()),

                Filter::make('manual_only')
                    ->label('القيود اليدوية فقط')
                    ->query(fn (Builder $query): Builder => $query->where('entry_type', JournalEntry::TYPE_MANUAL)),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
            ]);
    }
}
