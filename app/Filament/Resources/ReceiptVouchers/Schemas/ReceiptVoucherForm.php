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
                    ->afterStateUpdated(fn (Set $set) => $set('allocations', [])),
            ]),

            Repeater::make('allocations')
                ->label('الفواتير المحصلة')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('sales_invoice_id')
                            ->label('فاتورة البيع')
                            ->options(fn (Get $get, ?ReceiptVoucher $record): array => self::invoiceOptions(
                                (int) $get('../../customer_id'),
                                $record?->getKey(),
                            ))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->live(),

                        TextInput::make('electronic_invoice_number')
                            ->label('رقم الفاتورة الإلكترونية')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->validationMessages([
                                'required' => 'رقم الفاتورة الإلكترونية مطلوب.',
                                'integer' => 'رقم الفاتورة الإلكترونية يجب أن يكون رقماً صحيحاً.',
                                'min' => 'رقم الفاتورة الإلكترونية يجب أن يكون أكبر من صفر.',
                            ]),

                        Placeholder::make('invoice_date')
                            ->label('تاريخ الفاتورة')
                            ->content(fn (Get $get): string => self::invoice($get('sales_invoice_id'))
                                ?->invoice_date?->format('Y-m-d') ?? '—'),

                        Placeholder::make('invoice_total')
                            ->label('إجمالي الفاتورة')
                            ->content(fn (Get $get): string => self::formatAmount(
                                self::invoice($get('sales_invoice_id'))?->totalAmount(),
                            )),

                        Placeholder::make('previously_paid')
                            ->label('المحصل سابقاً')
                            ->content(fn (Get $get, ?ReceiptVoucher $record): string => self::formatAmount(
                                self::invoice($get('sales_invoice_id'))
                                    ?->paidAmount($record?->getKey()),
                            )),

                        Placeholder::make('remaining_before_receipt')
                            ->label('المتبقي قبل هذا السند')
                            ->content(fn (Get $get, ?ReceiptVoucher $record): string => self::formatAmount(
                                self::invoice($get('sales_invoice_id'))
                                    ?->remainingAmount($record?->getKey()),
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
                ->reorderable(false)
                ->addActionLabel('إضافة فاتورة'),

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
     * @return array<int, string>
     */
    private static function invoiceOptions(int $customerId, ?int $excludingReceiptVoucherId): array
    {
        if ($customerId <= 0) {
            return [];
        }

        return SalesInvoice::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (SalesInvoice $invoice): bool => $invoice->remainingAmount(
                $excludingReceiptVoucherId,
            ) > 0)
            ->mapWithKeys(fn (SalesInvoice $invoice): array => [
                $invoice->getKey() => $invoice->document_number
                    . ' — المتبقي: '
                    . self::formatAmount($invoice->remainingAmount($excludingReceiptVoucherId)),
            ])
            ->all();
    }

    private static function invoice(mixed $invoiceId): ?SalesInvoice
    {
        return $invoiceId ? SalesInvoice::query()->find($invoiceId) : null;
    }

    private static function formatAmount(mixed $amount): string
    {
        return number_format((float) ($amount ?? 0), 2);
    }
}
