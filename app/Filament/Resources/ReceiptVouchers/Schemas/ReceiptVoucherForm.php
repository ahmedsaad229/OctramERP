<?php

namespace App\Filament\Resources\ReceiptVouchers\Schemas;

use App\Models\ReceiptVoucher;
use App\Models\SalesInvoice;
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
                    ->afterStateUpdated(fn (Set $set) => $set('allocations', [[]])),
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
                                collect($get('../../allocations') ?? [])
                                    ->pluck('sales_invoice_id')
                                    ->filter()
                                    ->map(fn ($invoiceId): int => (int) $invoiceId)
                                    ->reject(fn (int $invoiceId): bool => $invoiceId === (int) $get('sales_invoice_id'))
                                    ->all(),
                            ))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->live()
                            ->afterStateUpdated(function (
                                Set $set,
                                mixed $state,
                                ?ReceiptVoucher $record,
                            ): void {
                                self::setInvoiceSummary($set, $state, $record?->getKey());
                            }),

                        Placeholder::make('invoice_code')
                            ->label('كود الفاتورة')
                            ->content(fn (Get $get): string => self::invoice($get('sales_invoice_id'))
                                ?->document_number ?? '—'),

                        Placeholder::make('invoice_date')
                            ->label('تاريخ الفاتورة')
                            ->content(fn (Get $get): string => self::invoice($get('sales_invoice_id'))
                                ?->invoice_date?->format('Y-m-d') ?? '—'),

                        TextInput::make('invoice_total')
                            ->label('إجمالي الفاتورة')
                            ->readOnly()
                            ->dehydrated(false)
                            ->default('0.00'),

                        TextInput::make('previously_paid')
                            ->label('المحصل سابقاً')
                            ->readOnly()
                            ->dehydrated(false)
                            ->default('0.00'),

                        TextInput::make('remaining_before_receipt')
                            ->label('المتبقي قبل هذا السند')
                            ->readOnly()
                            ->dehydrated(false)
                            ->default('0.00')
                            ->afterStateHydrated(fn (
                                Set $set,
                                Get $get,
                                ?ReceiptVoucher $record,
                            ) => self::setInvoiceSummary(
                                $set,
                                $get('sales_invoice_id'),
                                $record?->getKey(),
                            )),

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
                )),

            Textarea::make('notes')
                ->label('البيان')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    /**
     * @param  array<int, int>  $excludedInvoiceIds
     * @return array<int, string>
     */
    private static function invoiceOptions(
        int $customerId,
        ?int $excludingReceiptVoucherId,
        array $excludedInvoiceIds = [],
    ): array
    {
        if ($customerId <= 0) {
            return [];
        }

        return SalesInvoice::query()
            ->where('customer_id', $customerId)
            ->whereNotNull('electronic_invoice_number')
            ->where('electronic_invoice_number', '>', 0)
            ->when(
                $excludedInvoiceIds !== [],
                fn ($query) => $query->whereKeyNot($excludedInvoiceIds),
            )
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (SalesInvoice $invoice): bool => $invoice->remainingAmount(
                $excludingReceiptVoucherId,
            ) > 0)
            ->mapWithKeys(fn (SalesInvoice $invoice): array => [
                $invoice->getKey() => $invoice->electronic_invoice_number
                    . ' — '
                    . $invoice->document_number
                    . ' — المتبقي: '
                    . self::formatAmount($invoice->remainingAmount($excludingReceiptVoucherId)),
            ])
            ->all();
    }

    private static function invoice(mixed $invoiceId): ?SalesInvoice
    {
        return $invoiceId ? SalesInvoice::query()->find($invoiceId) : null;
    }

    private static function setInvoiceSummary(
        Set $set,
        mixed $invoiceId,
        ?int $excludingReceiptVoucherId,
    ): void {
        $invoice = self::invoice($invoiceId);

        $set('invoice_total', self::formatAmount($invoice?->totalAmount()));
        $set('previously_paid', self::formatAmount(
            $invoice?->paidAmount($excludingReceiptVoucherId),
        ));
        $set('remaining_before_receipt', self::formatAmount(
            $invoice?->remainingAmount($excludingReceiptVoucherId),
        ));
    }

    private static function formatAmount(mixed $amount): string
    {
        return number_format((float) ($amount ?? 0), 2);
    }
}
