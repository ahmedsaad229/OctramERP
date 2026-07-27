<?php

namespace App\Filament\Resources\ReceiptVouchers\Schemas;

use App\Enums\PaymentMethod;
use App\Models\ReceiptVoucher;
use App\Models\SalesInvoice;
use App\Support\DocumentFieldPresentation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ReceiptVoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('document_number')
                    ->label('رقم المستند')
                    ->placeholder('سيتم إنشاؤه تلقائياً')
                    ->readOnly()
                    ->dehydrated(false),

                DatePicker::make('date')
                    ->label('التاريخ')
                    ->default(now())
                    ->native(false)
                    ->required(),

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

                Select::make('customer_id')
                    ->label('العميل')
                    ->relationship(
                        'customer',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('active', true),
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('allocations', [[
                        'sales_invoice_id' => null,
                        'amount' => null,
                    ]])),

                Select::make('payment_method')
                    ->label('طريقة الدفع')
                    ->options(PaymentMethod::options())
                    ->default(PaymentMethod::Cash->value)
                    ->required()
                    ->native(false),
            ]),

            Repeater::make('allocations')
                ->label('الفواتير المحصلة')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('sales_invoice_id')
                            ->label('رقم الفاتورة الإلكترونية')
                            ->options(fn (Get $get, ?ReceiptVoucher $record): array => self::invoiceOptions(
                                (int) $get('../../customer_id'),
                                $record?->getKey(),
                            ))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                $set('sales_invoice_id', filled($state) ? (int) $state : null);
                                $set('amount', null);
                            }),

                        Placeholder::make('invoice_code_display')
                            ->label('كود الفاتورة')
                            ->content(fn (Get $get, ?ReceiptVoucher $record): string => self::invoiceSummary(
                                self::selectedInvoiceId($get),
                                $record?->getKey(),
                            )['invoice_code'] ?? '—')
                            ->extraAttributes(DocumentFieldPresentation::itemCode())
                            ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                            ->alignCenter(),

                        Placeholder::make('invoice_date_display')
                            ->label('تاريخ الفاتورة')
                            ->content(fn (Get $get, ?ReceiptVoucher $record): string => self::invoiceSummary(
                                self::selectedInvoiceId($get),
                                $record?->getKey(),
                            )['invoice_date'] ?? '—')
                            ->extraAttributes(DocumentFieldPresentation::value(true))
                            ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                            ->alignCenter(),

                        Placeholder::make('invoice_total_display')
                            ->label('إجمالي الفاتورة')
                            ->content(fn (Get $get, ?ReceiptVoucher $record): string => self::formatAmount(
                                self::invoiceSummary(
                                    self::selectedInvoiceId($get),
                                    $record?->getKey(),
                                )['invoice_total'],
                            ))
                            ->extraAttributes(DocumentFieldPresentation::money())
                            ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                            ->alignCenter(),

                        Placeholder::make('previously_paid_display')
                            ->label('المحصل سابقاً')
                            ->content(fn (Get $get, ?ReceiptVoucher $record): string => self::formatAmount(
                                self::invoiceSummary(
                                    self::selectedInvoiceId($get),
                                    $record?->getKey(),
                                )['previously_paid'],
                            ))
                            ->extraAttributes(DocumentFieldPresentation::money())
                            ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                            ->alignCenter(),

                        Placeholder::make('remaining_before_receipt_display')
                            ->label('المتبقي قبل هذا السند')
                            ->content(fn (Get $get, ?ReceiptVoucher $record): string => self::formatAmount(
                                self::invoiceSummary(
                                    self::selectedInvoiceId($get),
                                    $record?->getKey(),
                                )['remaining_before_receipt'],
                            ))
                            ->extraAttributes(DocumentFieldPresentation::money())
                            ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                            ->alignCenter(),

                        TextInput::make('amount')
                            ->label('المبلغ المحصل الآن')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->live(),
                    ]),
                ])
                ->defaultItems(1)
                ->minItems(1)
                ->maxItems(1)
                ->addable(false)
                ->deletable(false)
                ->reorderable(false),

            Placeholder::make('allocation_total')
                ->label('إجمالي المبلغ المحصل')
                ->content(fn (Get $get): string => self::formatAmount(
                    collect($get('allocations') ?? [])->sum(
                        fn (array $allocation): float => (float) ($allocation['amount'] ?? 0),
                    ),
                ))
                ->extraAttributes(DocumentFieldPresentation::money())
                ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                ->alignCenter(),

            Textarea::make('notes')
                ->label('البيان')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private static function invoiceOptions(
        int $customerId,
        ?int $excludingReceiptVoucherId,
    ): array {
        if ($customerId <= 0) {
            return [];
        }

        return SalesInvoice::query()
            ->where('customer_id', $customerId)
            ->whereNotNull('electronic_invoice_number')
            ->where('electronic_invoice_number', '>', 0)
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (SalesInvoice $invoice): bool => $invoice->remainingAmount(
                $excludingReceiptVoucherId,
            ) > 0)
            ->mapWithKeys(fn (SalesInvoice $invoice): array => [
                (int) $invoice->getKey() => $invoice->electronic_invoice_number
                    .' — '
                    .$invoice->document_number
                    .' — المتبقي: '
                    .self::formatAmount($invoice->remainingAmount($excludingReceiptVoucherId)),
            ])
            ->all();
    }

    private static function invoice(mixed $invoiceId): ?SalesInvoice
    {
        return $invoiceId ? SalesInvoice::query()->find($invoiceId) : null;
    }

    /**
     * @return array{
     *     invoice_code: ?string,
     *     invoice_date: ?string,
     *     invoice_total: float,
     *     previously_paid: float,
     *     remaining_before_receipt: float
     * }
     */
    public static function invoiceSummary(
        ?int $invoiceId,
        ?int $excludingReceiptVoucherId,
    ): array {
        $invoice = self::invoice($invoiceId);

        return [
            'invoice_code' => $invoice?->document_number,
            'invoice_date' => $invoice?->invoice_date?->format('Y-m-d'),
            'invoice_total' => $invoice?->totalAmount() ?? 0,
            'previously_paid' => $invoice?->paidAmount($excludingReceiptVoucherId) ?? 0,
            'remaining_before_receipt' => $invoice?->remainingAmount($excludingReceiptVoucherId) ?? 0,
        ];
    }

    private static function selectedInvoiceId(Get $get): ?int
    {
        $invoiceId = $get('sales_invoice_id');

        return filled($invoiceId) ? (int) $invoiceId : null;
    }

    private static function formatAmount(mixed $amount): string
    {
        return number_format((float) ($amount ?? 0), 2).' ج.م';
    }
}
