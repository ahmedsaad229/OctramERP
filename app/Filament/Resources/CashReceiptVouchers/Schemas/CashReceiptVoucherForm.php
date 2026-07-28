<?php

namespace App\Filament\Resources\CashReceiptVouchers\Schemas;

use App\Enums\PaymentMethod;
use App\Models\ReceiptVoucher;
use App\Support\QuantityFormatter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CashReceiptVoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('بيانات سند الاستلام النقدي')->schema([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])->schema([
                    TextInput::make('document_number')->label('رقم السند')
                        ->placeholder('سيتم إنشاؤه تلقائيًا')->readOnly()->dehydrated(false),
                    DatePicker::make('date')->label('التاريخ')->default(now())->native(false)->required(),
                    Select::make('receipt_type')->label('نوع الاستلام')
                        ->options(ReceiptVoucher::receiptTypeOptions())
                        ->default(ReceiptVoucher::TYPE_CUSTOMER)->required()->native(false)->live()
                        ->afterStateUpdated(function (Set $set, mixed $state): void {
                            if ($state === ReceiptVoucher::TYPE_GENERAL) {
                                $set('customer_id', null);
                            } else {
                                $set('payer_name', null);
                                $set('receipt_reason', null);
                            }
                        }),
                    Select::make('treasury_id')->label('الخزينة')
                        ->relationship('treasury', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true))
                        ->searchable()->preload()->required(),
                    TextInput::make('amount')->label('المبلغ')->type('text')->inputMode('decimal')
                        ->formatStateUsing(fn (mixed $state): ?string => QuantityFormatter::normalizeForInput($state))
                        ->mutateStateForValidationUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                        ->dehydrateStateUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                        ->extraInputAttributes(QuantityFormatter::inputAttributes())
                        ->rules(['numeric', 'gt:0'])->required(),
                    Select::make('payment_method')->label('طريقة الاستلام')
                        ->options(PaymentMethod::options())->default(PaymentMethod::Cash->value)->required()->native(false),
                    Select::make('customer_id')->label('العميل')
                        ->relationship('customer', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('active', true))
                        ->searchable()->preload()
                        ->visible(fn (Get $get): bool => $get('receipt_type') === ReceiptVoucher::TYPE_CUSTOMER)
                        ->required(fn (Get $get): bool => $get('receipt_type') === ReceiptVoucher::TYPE_CUSTOMER),
                    TextInput::make('payer_name')->label('اسم الدافع أو الجهة')
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => $get('receipt_type') === ReceiptVoucher::TYPE_GENERAL),
                    Select::make('receipt_reason')->label('سبب الاستلام')
                        ->options(ReceiptVoucher::receiptReasonOptions())->native(false)
                        ->visible(fn (Get $get): bool => $get('receipt_type') === ReceiptVoucher::TYPE_GENERAL),
                    TextInput::make('reference_number')->label('الرقم المرجعي')->maxLength(255),
                ])->columnSpanFull(),
                Textarea::make('notes')->label('البيان')->rows(3)->maxLength(2000)->columnSpanFull(),
            ]),
        ]);
    }
}
