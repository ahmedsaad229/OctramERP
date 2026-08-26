<?php

namespace App\Filament\Resources\CashPaymentVouchers\Schemas;

use App\Models\Account;

use App\Enums\PaymentMethod;
use App\Models\SupplierPaymentVoucher;
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

class CashPaymentVoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('بيانات سند الصرف النقدي')->schema([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])->schema([
                    TextInput::make('document_number')->label('رقم السند')
                        ->placeholder('سيتم إنشاؤه تلقائيًا')->readOnly()->dehydrated(false),
                    DatePicker::make('voucher_date')->label('التاريخ')->default(now())->native(false)->required(),
                    Select::make('payment_type')->label('نوع الصرف')
                        ->options(SupplierPaymentVoucher::paymentTypeOptions())
                        ->default(SupplierPaymentVoucher::TYPE_SUPPLIER)->required()->native(false)->live()
                        ->afterStateUpdated(function (Set $set, mixed $state): void {
                            if ($state === SupplierPaymentVoucher::TYPE_GENERAL) {
                                $set('supplier_id', null);
                            } else {
                                $set('beneficiary_name', null);
                                $set('payment_reason', null);
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
                    Select::make('payment_method')->label('طريقة الدفع')
                        ->options(PaymentMethod::options())->default(PaymentMethod::Cash->value)->required()->native(false),
                    Select::make('supplier_id')->label('المورد')
                        ->relationship('supplier', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('active', true))
                        ->searchable()->preload()
                        ->visible(fn (Get $get): bool => $get('payment_type') === SupplierPaymentVoucher::TYPE_SUPPLIER)
                        ->required(fn (Get $get): bool => $get('payment_type') === SupplierPaymentVoucher::TYPE_SUPPLIER),
                    TextInput::make('beneficiary_name')->label('اسم المستفيد أو الجهة')->maxLength(255)
                        ->visible(fn (Get $get): bool => $get('payment_type') === SupplierPaymentVoucher::TYPE_GENERAL),
                    Select::make('expense_account_id')
                        ->label('سبب الصرف / الحساب')
                        ->options(
                            fn (): array => Account::query()
                                ->posting()
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(
                                    fn (Account $account): array => [
                                        $account->getKey() => $account->displayName(),
                                    ]
                                )
                                ->all()
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(
                            fn (Get $get): bool =>
                                $get('payment_type') === SupplierPaymentVoucher::TYPE_GENERAL
                        )
                        ->required(
                            fn (Get $get): bool =>
                                $get('payment_type') === SupplierPaymentVoucher::TYPE_GENERAL
                        ),
                    TextInput::make('reference_number')->label('الرقم المرجعي')->maxLength(255),
                ])->columnSpanFull(),
                Textarea::make('notes')->label('البيان')->rows(3)->maxLength(2000)->columnSpanFull(),
            ]),
        ]);
    }
}
