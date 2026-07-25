<?php

namespace App\Services;

use App\Models\PartyTransaction;
use App\Models\ReceiptVoucher;
use App\Models\SalesInvoice;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptVoucherService
{
    public function __construct(
        private readonly PartyTransactionService $partyTransactionService,
        private readonly DocumentNumberService $documentNumberService,
        private readonly TreasuryTransactionService $treasuryTransactionService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ReceiptVoucher
    {
        return DB::transaction(function () use ($data): ReceiptVoucher {
            $allocations = $data['allocations'] ?? [];
            unset($data['allocations'], $data['document_number']);

            $data['document_number'] = $this->documentNumberService
                ->generate(DocumentNumberService::RECEIPT_VOUCHER);
            $data['amount'] = 0;
            $data['created_by'] = auth()->id();
            $voucher = ReceiptVoucher::create($data);

            $this->persistAllocations($voucher, $allocations);
            $this->applyFinancialEffect($voucher);

            return $voucher->fresh(['treasury', 'customer', 'creator', 'allocations']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ReceiptVoucher $voucher, array $data): ReceiptVoucher
    {
        return DB::transaction(function () use ($voucher, $data): ReceiptVoucher {
            $voucher = ReceiptVoucher::query()
                ->lockForUpdate()
                ->findOrFail($voucher->getKey());

            $allocations = $data['allocations'] ?? [];
            $invoiceIds = [
                ...$voucher->allocations()->pluck('sales_invoice_id')->all(),
                ...array_column($allocations, 'sales_invoice_id'),
            ];

            SalesInvoice::query()
                ->whereKey(array_values(array_unique(array_filter($invoiceIds))))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $this->removeFinancialEffect($voucher);
            $voucher->allocations()->delete();

            unset($data['allocations'], $data['amount'], $data['document_number'], $data['created_by']);
            $voucher->update($data);

            $this->persistAllocations($voucher, $allocations);
            $this->applyFinancialEffect($voucher);

            return $voucher->fresh(['treasury', 'customer', 'creator', 'allocations']);
        });
    }

    public function delete(ReceiptVoucher $voucher): bool
    {
        return DB::transaction(function () use ($voucher): bool {
            $voucher = ReceiptVoucher::query()
                ->lockForUpdate()
                ->findOrFail($voucher->getKey());

            $this->removeFinancialEffect($voucher);
            $voucher->allocations()->delete();

            return (bool) $voucher->delete();
        });
    }

    private function applyFinancialEffect(ReceiptVoucher $voucher): void
    {
        $treasury = Treasury::query()
            ->findOrFail($voucher->treasury_id);

        $this->treasuryTransactionService->replaceForSource(
            $treasury,
            $voucher,
            $voucher->date,
            TreasuryTransaction::TYPE_RECEIPT,
            (float) $voucher->amount,
            TreasuryTransaction::DIRECTION_DEBIT,
            $voucher->document_number,
            $voucher->notes,
            $voucher->created_by,
        );

        $this->partyTransactionService->replaceDocumentTransaction(
            $voucher->customer()->firstOrFail(),
            PartyTransaction::TYPE_CUSTOMER_CREDIT,
            $voucher,
            $voucher->date,
            0,
            (float) $voucher->amount,
            $voucher->document_number,
            $voucher->notes,
        );
    }

    private function removeFinancialEffect(ReceiptVoucher $voucher): void
    {
        $this->treasuryTransactionService->deleteForSource($voucher);
        $this->partyTransactionService->deleteDocumentTransaction($voucher);
    }

    /**
     * @param  array<int, array<string, mixed>>  $allocations
     */
    private function persistAllocations(ReceiptVoucher $voucher, array $allocations): void
    {
        if ($allocations === []) {
            throw ValidationException::withMessages([
                'data.allocations' => 'يجب إضافة فاتورة واحدة على الأقل للتحصيل.',
            ]);
        }

        $invoiceIds = [];
        $validatedAllocations = [];

        foreach ($allocations as $index => $allocation) {
            $invoiceId = (int) ($allocation['sales_invoice_id'] ?? 0);
            $amount = (float) ($allocation['amount'] ?? 0);

            if ($invoiceId <= 0) {
                throw ValidationException::withMessages([
                    "data.allocations.{$index}.sales_invoice_id" => 'يجب اختيار رقم الفاتورة الإلكترونية.',
                ]);
            }

            if (isset($invoiceIds[$invoiceId])) {
                throw ValidationException::withMessages([
                    "data.allocations.{$index}.sales_invoice_id" => 'لا يمكن تكرار نفس الفاتورة داخل سند القبض.',
                ]);
            }

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    "data.allocations.{$index}.amount" => 'يجب أن يكون المبلغ المحصل أكبر من صفر.',
                ]);
            }

            $invoiceIds[$invoiceId] = true;
            $validatedAllocations[] = [
                'sales_invoice_id' => $invoiceId,
                'amount' => $amount,
                'index' => $index,
            ];
        }

        $lockedInvoices = SalesInvoice::query()
            ->whereKey(array_keys($invoiceIds))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($validatedAllocations as $allocation) {
            $invoice = $lockedInvoices->get($allocation['sales_invoice_id']);
            $index = $allocation['index'];

            if (! $invoice) {
                throw ValidationException::withMessages([
                    "data.allocations.{$index}.sales_invoice_id" => 'فاتورة البيع غير موجودة.',
                ]);
            }

            if ((int) $invoice->customer_id !== (int) $voucher->customer_id) {
                throw ValidationException::withMessages([
                    "data.allocations.{$index}.sales_invoice_id" => 'الفاتورة لا تخص العميل المحدد.',
                ]);
            }

            if (! is_int($invoice->electronic_invoice_number) || $invoice->electronic_invoice_number < 1) {
                throw ValidationException::withMessages([
                    "data.allocations.{$index}.sales_invoice_id" => 'فاتورة البيع لا تحتوي على رقم فاتورة إلكترونية صالح.',
                ]);
            }

            $invoiceTotal = (float) $invoice->items()->sum('line_total');
            $previouslyPaid = (float) $invoice->receiptAllocations()->sum('amount');
            $remainingAmount = max(0, $invoiceTotal - $previouslyPaid);

            if ($allocation['amount'] > $remainingAmount) {
                throw ValidationException::withMessages([
                    "data.allocations.{$index}.amount" => 'المبلغ المحصل أكبر من المتبقي على الفاتورة.',
                ]);
            }
        }

        $voucher->allocations()->createMany(
            array_map(
                fn (array $allocation): array => [
                    'sales_invoice_id' => $allocation['sales_invoice_id'],
                    'amount' => round($allocation['amount'], 2),
                ],
                $validatedAllocations,
            ),
        );

        $persistedTotal = (float) $voucher->allocations()->sum('amount');

        if ($persistedTotal <= 0) {
            throw ValidationException::withMessages([
                'data.allocations' => 'يجب أن يكون إجمالي المبلغ المحصل أكبر من صفر.',
            ]);
        }

        $voucher->update(['amount' => $persistedTotal]);
    }
}
