<?php

namespace App\Filament\Resources\SupplierPaymentVouchers\Schemas;

use App\Enums\PaymentMethod;
use App\Models\PurchaseInvoice;
use App\Models\SupplierPaymentVoucher;
use App\Support\ArabicMoney;
use App\Support\DocumentFieldPresentation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SupplierPaymentVoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('بيانات سند الصرف')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 3,
                        ])->schema([
                            TextInput::make('document_number')
                                ->label('رقم المستند')
                                ->placeholder('سيتم إنشاؤه تلقائيًا')
                                ->readOnly()
                                ->dehydrated(false),
                            DatePicker::make('voucher_date')
                                ->label('تاريخ السند')
                                ->default(now())
                                ->native(false)
                                ->required()
                                ->live(),
                            Select::make('supplier_id')
                                ->label('المورد')
                                ->relationship(
                                    'supplier',
                                    'name',
                                    modifyQueryUsing: fn (Builder $query): Builder => $query->where('active', true),
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Set $set) => $set('purchase_invoice_id', null)),
                            Select::make('treasury_id')
                                ->label('الخزينة')
                                ->relationship(
                                    'treasury',
                                    'name',
                                    modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true),
                                )
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('payment_method')
                                ->label('طريقة الدفع')
                                ->options(PaymentMethod::options())
                                ->default(PaymentMethod::Cash->value)
                                ->required()
                                ->native(false),
                            TextInput::make('amount')
                                ->label('قيمة السند')
                                ->numeric()
                                ->minValue(0.01)
                                ->step(0.01)
                                ->required()
                                ->live(),
                        ]),
                    ]),
                Section::make('سداد فاتورة الشراء')
                    ->schema([
                        Select::make('purchase_invoice_id')
                            ->label('فاتورة الشراء')
                            ->key(fn (Get $get): string => 'purchase-invoice-'.($get('supplier_id') ?: 'none'))
                            ->options(fn (Get $get, ?SupplierPaymentVoucher $record): array => self::invoiceOptions(
                                (int) $get('supplier_id'),
                                $record?->getKey(),
                            ))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->live(),
                        Grid::make([
                            'default' => 1,
                            'sm' => 2,
                            'xl' => 4,
                        ])->schema([
                            Placeholder::make('invoice_total_display')
                                ->label('إجمالي الفاتورة')
                                ->content(fn (Get $get, ?SupplierPaymentVoucher $record): string => ArabicMoney::format(
                                    self::invoiceSummaryFromForm($get, $record)['invoice_total'],
                                ))
                                ->extraAttributes(DocumentFieldPresentation::money())
                                ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                ->alignCenter(),
                            Placeholder::make('previously_paid_display')
                                ->label('المدفوع سابقًا')
                                ->content(fn (Get $get, ?SupplierPaymentVoucher $record): string => ArabicMoney::format(
                                    self::invoiceSummaryFromForm($get, $record)['previously_paid'],
                                ))
                                ->extraAttributes(DocumentFieldPresentation::money())
                                ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                ->alignCenter(),
                            Placeholder::make('current_payment_display')
                                ->label('الدفعة الحالية')
                                ->content(fn (Get $get): string => ArabicMoney::format($get('amount')))
                                ->extraAttributes(DocumentFieldPresentation::money())
                                ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                ->alignCenter(),
                            Placeholder::make('remaining_after_payment_display')
                                ->label('المتبقي بعد الدفع')
                                ->content(fn (Get $get, ?SupplierPaymentVoucher $record): string => ArabicMoney::format(
                                    self::invoiceSummaryFromForm($get, $record)['remaining_after_payment'],
                                ))
                                ->extraAttributes(DocumentFieldPresentation::money())
                                ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                ->alignCenter(),
                        ]),
                    ]),
                Section::make('البيان')
                    ->schema([
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function invoiceOptions(
        int $supplierId,
        ?int $excludingVoucherId = null,
    ): array {
        if ($supplierId <= 0) {
            return [];
        }

        return PurchaseInvoice::query()
            ->where('supplier_id', $supplierId)
            ->with(['items', 'supplierPaymentAllocations'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (PurchaseInvoice $invoice): bool => $invoice->remainingAmount($excludingVoucherId) > 0)
            ->mapWithKeys(fn (PurchaseInvoice $invoice): array => [
                $invoice->getKey() => self::invoiceLabel($invoice)
                    .' — المتبقي: '
                    .ArabicMoney::format($invoice->remainingAmount($excludingVoucherId)),
            ])
            ->all();
    }

    /**
     * @return array{invoice_total: float, previously_paid: float, remaining_after_payment: float}
     */
    public static function invoiceSummary(
        ?int $invoiceId,
        ?SupplierPaymentVoucher $voucher,
        mixed $voucherDate,
        mixed $amount,
    ): array {
        $invoice = $invoiceId
            ? PurchaseInvoice::query()->with('items')->find($invoiceId)
            : null;

        if (! $invoice) {
            return [
                'invoice_total' => 0.0,
                'previously_paid' => 0.0,
                'remaining_after_payment' => 0.0,
            ];
        }

        if ($voucher) {
            $contextVoucher = clone $voucher;
            $contextVoucher->voucher_date = $voucherDate ?: $voucher->voucher_date;
            $previouslyPaid = $invoice->previouslyPaidBeforeSupplierPayment($contextVoucher);
        } else {
            $date = filled($voucherDate) ? $voucherDate : now()->toDateString();
            $previouslyPaid = (float) $invoice->supplierPaymentAllocations()
                ->whereHas(
                    'supplierPaymentVoucher',
                    fn (Builder $query): Builder => $query->whereDate('voucher_date', '<=', $date),
                )
                ->sum('amount');
        }

        return [
            'invoice_total' => $invoice->totalAmount(),
            'previously_paid' => $previouslyPaid,
            'remaining_after_payment' => max(
                0,
                $invoice->totalAmount() - $previouslyPaid - (float) ($amount ?? 0),
            ),
        ];
    }

    /**
     * @return array{invoice_total: float, previously_paid: float, remaining_after_payment: float}
     */
    private static function invoiceSummaryFromForm(
        Get $get,
        ?SupplierPaymentVoucher $voucher,
    ): array {
        $invoiceId = $get('purchase_invoice_id');

        return self::invoiceSummary(
            filled($invoiceId) ? (int) $invoiceId : null,
            $voucher,
            $get('voucher_date'),
            $get('amount'),
        );
    }

    private static function invoiceLabel(PurchaseInvoice $invoice): string
    {
        if (filled($invoice->invoice_number)) {
            return "فاتورة {$invoice->invoice_number} — {$invoice->code}";
        }

        return "فاتورة {$invoice->code}";
    }
}
