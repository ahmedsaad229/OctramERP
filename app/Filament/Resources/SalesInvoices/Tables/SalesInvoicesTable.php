<?php

namespace App\Filament\Resources\SalesInvoices\Tables;

use App\Filament\Actions\ProtectedDeleteAction;

use App\Enums\PaymentType;
use App\Enums\TaxType;
use App\Models\SalesInvoice;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-document-text')
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الفاتورة'),

            TextColumn::make('electronic_invoice_number')
                ->label('رقم الفاتورة الإلكترونية')
                ->searchable()
                ->sortable()
                ->alignCenter(),

                TextColumn::make('invoice_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user'),

                TextColumn::make('total')
                    ->label('الإجمالي النهائي')
                    ->state(fn (SalesInvoice $record): float => $record->totalAmount())
                    ->money('EGP')
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('tax_type')
                    ->label('نوع الضريبة')
                    ->formatStateUsing(fn (TaxType $state): string => $state->label())
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tax_amount')
                    ->label('قيمة الضريبة')
                    ->numeric(2)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payment_type')
                    ->label('نوع التعامل')
                    ->formatStateUsing(fn (PaymentType $state): string => $state->label())
                    ->badge()
                    ->icon(fn (PaymentType $state): string => $state === PaymentType::Cash
                        ? 'heroicon-o-banknotes'
                        : 'heroicon-o-clock')
                    ->color(fn (PaymentType $state): string => $state === PaymentType::Cash
                        ? 'success'
                        : 'info'),

                TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                TextColumn::make('due_status')
                    ->label('حالة الاستحقاق')
                    ->state(fn (SalesInvoice $record): string => $record->dueStatusLabel())
                    ->badge()
                    ->icon(fn (SalesInvoice $record): string => match ($record->dueStatus()) {
                        SalesInvoice::DUE_STATUS_UPCOMING => 'heroicon-o-clock',
                        SalesInvoice::DUE_STATUS_TODAY => 'heroicon-o-exclamation-circle',
                        SalesInvoice::DUE_STATUS_OVERDUE => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-check-circle',
                    })
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
                    ->icon('heroicon-o-building-storefront')
                    ->toggleable(),

                TextColumn::make('items_count')
                    ->label('عدد الأصناف')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-cube')
                    ->toggleable(),
            ])
            ->filters(self::filters())
            ->recordActions([
                ViewAction::make()
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->color('info'),

                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary'),

                Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (SalesInvoice $record): string => route(
                        'sales-invoices.print',
                        $record
                    ))
                    ->openUrlInNewTab(),


                ProtectedDeleteAction::make()
                    ->iconButton()
                    ->tooltip('حذف'),
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

            SelectFilter::make('tax_type')
                ->label('نوع الضريبة')
                ->options(TaxType::options()),

            Filter::make('due_date')
                ->label('تاريخ الاستحقاق')
                ->schema([
                    DatePicker::make('from')->label('من'),
                    DatePicker::make('until')->label('إلى'),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when(
                        $data['from'] ?? null,
                        fn (Builder $query, mixed $date): Builder => $query
                            ->whereDate('due_date', '>=', $date)
                    )
                    ->when(
                        $data['until'] ?? null,
                        fn (Builder $query, mixed $date): Builder => $query
                            ->whereDate('due_date', '<=', $date)
                    )),

            Filter::make('overdue')
                ->label('المتأخرة فقط')
                ->query(fn (Builder $query): Builder => $query
                    ->dueStatus(SalesInvoice::DUE_STATUS_OVERDUE)),
        ];
    }
}
