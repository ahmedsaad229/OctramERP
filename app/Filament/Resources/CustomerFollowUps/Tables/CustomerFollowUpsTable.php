<?php

namespace App\Filament\Resources\CustomerFollowUps\Tables;

use App\Models\CustomerFollowUp;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerFollowUpsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_at', 'desc')
            ->emptyStateHeading('لا توجد متابعات عملاء')
            ->emptyStateDescription('ابدأ بتسجيل أول متابعة مع أحد العملاء.')
            ->columns([
                TextColumn::make('follow_up_number')
                    ->label('رقم المتابعة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('scheduled_at')
                    ->label('الموعد')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable()
                    ->color(
                        fn (CustomerFollowUp $record): string =>
                            $record->isOverdue() ? 'danger' : 'gray'
                    ),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact_person')
                    ->label('مسؤول العميل')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('نوع المتابعة')
                    ->formatStateUsing(
                        fn (string $state): string =>
                            CustomerFollowUp::typeOptions()[$state] ?? $state
                    )
                    ->badge(),

                TextColumn::make('salesResponsible.name')
                    ->label('مسؤول المبيعات')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->state(
                        fn (CustomerFollowUp $record): string =>
                            $record->isOverdue() ? 'overdue' : $record->status
                    )
                    ->formatStateUsing(
                        fn (string $state): string => match ($state) {
                            'overdue' => 'متأخرة',
                            CustomerFollowUp::STATUS_COMPLETED => 'تمت',
                            CustomerFollowUp::STATUS_POSTPONED => 'مؤجلة',
                            CustomerFollowUp::STATUS_CANCELLED => 'ملغاة',
                            CustomerFollowUp::STATUS_NO_ANSWER => 'لم يتم التواصل',
                            default => 'مجدولة',
                        }
                    )
                    ->color(
                        fn (string $state): string => match ($state) {
                            'overdue' => 'danger',
                            CustomerFollowUp::STATUS_COMPLETED => 'success',
                            CustomerFollowUp::STATUS_POSTPONED => 'warning',
                            CustomerFollowUp::STATUS_CANCELLED => 'gray',
                            CustomerFollowUp::STATUS_NO_ANSWER => 'danger',
                            default => 'info',
                        }
                    )
                    ->badge(),

                TextColumn::make('result')
                    ->label('النتيجة')
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            CustomerFollowUp::resultOptions()[$state] ?? ($state ?: '—')
                    )
                    ->limit(30),

                TextColumn::make('next_follow_up_at')
                    ->label('المتابعة القادمة')
                    ->dateTime('d/m/Y h:i A')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('sales_responsible_id')
                    ->label('مسؤول المبيعات')
                    ->relationship('salesResponsible', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->label('نوع المتابعة')
                    ->options(CustomerFollowUp::typeOptions()),

                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(CustomerFollowUp::statusOptions()),

                Filter::make('today')
                    ->label('متابعات اليوم')
                    ->query(
                        fn (Builder $query): Builder =>
                            $query->whereDate('scheduled_at', today())
                    ),

                Filter::make('overdue')
                    ->label('المتابعات المتأخرة')
                    ->query(
                        fn (Builder $query): Builder =>
                            $query->overdue()
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('فتح المتابعة'),

                Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(
                        fn (CustomerFollowUp $record): string => route(
                            'customer-follow-ups.print',
                            ['customerFollowUp' => $record]
                        )
                    )
                    ->openUrlInNewTab(),
            ]);
    }
}
