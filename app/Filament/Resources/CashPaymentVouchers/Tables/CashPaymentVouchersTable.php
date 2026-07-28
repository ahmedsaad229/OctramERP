<?php

namespace App\Filament\Resources\CashPaymentVouchers\Tables;

use App\Models\SupplierPaymentVoucher;
use App\Support\ArabicMoney;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CashPaymentVouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('document_number')->label('رقم السند')->searchable()->sortable(),
                TextColumn::make('voucher_date')->label('التاريخ')->date('d/m/Y')->sortable(),
                TextColumn::make('payment_type')->label('نوع الصرف')
                    ->formatStateUsing(fn (string $state): string => SupplierPaymentVoucher::paymentTypeOptions()[$state] ?? 'صرف نقدية')->badge(),
                TextColumn::make('paid_to')->label('المورد / المستفيد')
                    ->state(fn (SupplierPaymentVoucher $record): string => $record->paidToName())
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('beneficiary_name', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn (Builder $supplierQuery): Builder => $supplierQuery
                            ->where('name', 'like', "%{$search}%"))),
                TextColumn::make('treasury.name')->label('الخزينة')->sortable(),
                TextColumn::make('amount')->label('المبلغ')
                    ->formatStateUsing(fn (mixed $state): string => ArabicMoney::format($state))->alignCenter(),
                TextColumn::make('reference_number')->label('الرقم المرجعي')->searchable()->placeholder('—'),
                TextColumn::make('notes')->label('البيان')->searchable()->limit(40),
            ])
            ->filters([
                SelectFilter::make('payment_type')->label('نوع الصرف')->options(SupplierPaymentVoucher::paymentTypeOptions()),
                SelectFilter::make('treasury_id')->label('الخزينة')->relationship('treasury', 'name'),
                SelectFilter::make('supplier_id')->label('المورد')->relationship('supplier', 'name')->searchable()->preload(),
                SelectFilter::make('payment_reason')->label('سبب الصرف')->options(SupplierPaymentVoucher::paymentReasonOptions()),
                Filter::make('date_range')->label('الفترة')->schema([
                    DatePicker::make('from')->label('من تاريخ'),
                    DatePicker::make('to')->label('إلى تاريخ'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('voucher_date', '>=', $date))
                    ->when($data['to'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('voucher_date', '<=', $date))),
            ])
            ->recordActions([EditAction::make()]);
    }
}
