<?php

namespace App\Filament\Resources\SalesInvoices\Tables;

use App\Enums\PaymentType;
use App\Models\SalesInvoice;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['items', 'customer', 'warehouse'])
                ->withCount('items'))
            ->columns([
                TextColumn::make('document_number')
                    ->label('رقم المستند')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total')
                    ->label('إجمالي الفاتورة')
                    ->state(fn (SalesInvoice $record): float => $record->totalAmount())
                    ->money('EGP'),
                TextColumn::make('payment_type')
                    ->label('نوع التعامل')
                    ->formatStateUsing(fn (PaymentType $state): string => $state->label())
                    ->badge()
                    ->color(fn (PaymentType $state): string => $state === PaymentType::Cash ? 'success' : 'info'),
                TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('due_status')
                    ->label('حالة الاستحقاق')
                    ->state(fn (SalesInvoice $record): string => $record->dueStatusLabel())
                    ->badge()
                    ->color(fn (SalesInvoice $record): string => match ($record->dueStatus()) {
                        SalesInvoice::DUE_STATUS_UPCOMING => 'info',
                        SalesInvoice::DUE_STATUS_TODAY => 'warning',
                        SalesInvoice::DUE_STATUS_OVERDUE => 'danger',
                        default => 'success',
                    }),
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('items_count')
                    ->label('عدد الأصناف')
                    ->badge()
                    ->toggleable(),
            ])
            ->filters(self::filters())
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /**
     * @return array<int, Filter|SelectFilter>
     */
    private static function filters(): array
    {
        return [
            SelectFilter::make('payment_type')
                ->label('نوع التعامل')
                ->options(PaymentType::options()),
            Filter::make('due_date')
                ->label('تاريخ الاستحقاق')
                ->schema([
                    DatePicker::make('from')->label('من'),
                    DatePicker::make('until')->label('إلى'),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, mixed $date): Builder => $query->whereDate('due_date', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, mixed $date): Builder => $query->whereDate('due_date', '<=', $date))),
            Filter::make('overdue')
                ->label('المتأخرة فقط')
                ->query(fn (Builder $query): Builder => $query->dueStatus(SalesInvoice::DUE_STATUS_OVERDUE)),
        ];
    }
}
