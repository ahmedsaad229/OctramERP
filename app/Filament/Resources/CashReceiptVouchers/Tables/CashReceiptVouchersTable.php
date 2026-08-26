<?php

namespace App\Filament\Resources\CashReceiptVouchers\Tables;

use App\Enums\PaymentMethod;
use App\Models\ReceiptVoucher;
use App\Support\ArabicMoney;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CashReceiptVouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->emptyStateHeading('لا توجد سندات استلام نقدية')
            ->columns([
                TextColumn::make('document_number')->label('رقم السند')->searchable()->sortable(),
                TextColumn::make('date')->label('التاريخ')->date('d/m/Y')->sortable(),
                TextColumn::make('receipt_type')->label('نوع الاستلام')
                    ->formatStateUsing(fn (string $state): string => ReceiptVoucher::receiptTypeOptions()[$state] ?? 'استلام نقدي')
                    ->badge(),
                TextColumn::make('received_from')->label('العميل / الجهة')
                    ->state(fn (ReceiptVoucher $record): string => $record->receivedFromName())
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('payer_name', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn (Builder $customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"))),
                TextColumn::make('treasury.name')->label('الخزينة')->sortable(),
                TextColumn::make('amount')->label('المبلغ')
                    ->formatStateUsing(fn ($state): string => ArabicMoney::format($state))->alignCenter(),
                TextColumn::make('reference_number')->label('الرقم المرجعي')->searchable()->placeholder('—'),
                TextColumn::make('notes')->label('البيان')->searchable()->limit(40),
            ])
            ->filters([
                SelectFilter::make('receipt_type')->label('نوع الاستلام')->options(ReceiptVoucher::receiptTypeOptions()),
                SelectFilter::make('treasury_id')->label('الخزينة')->relationship('treasury', 'name'),
                SelectFilter::make('customer_id')->label('العميل')->relationship('customer', 'name')->searchable()->preload(),
                Filter::make('date_range')->label('الفترة')->schema([
                    DatePicker::make('from')->label('من تاريخ'),
                    DatePicker::make('to')->label('إلى تاريخ'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date))
                    ->when($data['to'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date))),
                SelectFilter::make('payment_method')->label('طريقة الاستلام')->options(PaymentMethod::options()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),

                Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(
                        fn (ReceiptVoucher $record): string => route(
                            'cash-receipt-vouchers.print',
                            $record
                        )
                    )
                    ->openUrlInNewTab(),
            ]);
    }
}
