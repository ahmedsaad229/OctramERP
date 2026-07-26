<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\PartyTransaction;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SupplierPaymentVoucher;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SupplierPaymentVoucherService
{
    public function __construct(
        private readonly PartyTransactionService $partyTransactionService,
        private readonly DocumentNumberService $documentNumberService,
        private readonly TreasuryTransactionService $treasuryTransactionService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SupplierPaymentVoucher
    {
        return DB::transaction(function () use ($data): SupplierPaymentVoucher {
            $invoiceId = (int) ($data['purchase_invoice_id'] ?? 0);
            unset($data['purchase_invoice_id'], $data['document_number']);

            $this->validateInput($data, $invoiceId);

            $data['document_number'] = $this->documentNumberService
                ->generate(DocumentNumberService::PAYMENT_VOUCHER);
            $data['created_by'] = auth()->id();

            $voucher = SupplierPaymentVoucher::create($data);
            $this->persistAllocation($voucher, $invoiceId);
            $this->applyFinancialEffect($voucher);

            return $voucher->fresh($this->relations());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        SupplierPaymentVoucher $voucher,
        array $data,
    ): SupplierPaymentVoucher {
        return DB::transaction(function () use ($voucher, $data): SupplierPaymentVoucher {
            $voucher = SupplierPaymentVoucher::query()
                ->lockForUpdate()
                ->findOrFail($voucher->getKey());

            $invoiceId = (int) ($data['purchase_invoice_id'] ?? 0);
            $invoiceIds = [
                ...$voucher->allocations()->pluck('purchase_invoice_id')->all(),
                $invoiceId,
            ];

            PurchaseInvoice::query()
                ->whereKey(array_values(array_unique(array_filter($invoiceIds))))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $this->removeFinancialEffect($voucher);
            $voucher->allocations()->delete();

            unset(
                $data['purchase_invoice_id'],
                $data['document_number'],
                $data['created_by'],
            );
            $this->validateInput($data, $invoiceId);
            $voucher->update($data);

            $this->persistAllocation($voucher, $invoiceId);
            $this->applyFinancialEffect($voucher);

            return $voucher->fresh($this->relations());
        });
    }

    public function delete(SupplierPaymentVoucher $voucher): bool
    {
        return DB::transaction(function () use ($voucher): bool {
            $voucher = SupplierPaymentVoucher::query()
                ->lockForUpdate()
                ->findOrFail($voucher->getKey());

            $this->removeFinancialEffect($voucher);
            $voucher->allocations()->delete();

            return (bool) $voucher->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateInput(array $data, int $invoiceId): void
    {
        $supplierId = (int) ($data['supplier_id'] ?? 0);
        $treasuryId = (int) ($data['treasury_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);
        $paymentMethod = $data['payment_method'] instanceof PaymentMethod
            ? $data['payment_method']
            : PaymentMethod::tryFrom((string) ($data['payment_method'] ?? ''));

        if (! Supplier::query()->whereKey($supplierId)->exists()) {
            throw ValidationException::withMessages([
                'data.supplier_id' => 'المورد المحدد غير موجود.',
            ]);
        }

        if (! Treasury::query()->whereKey($treasuryId)->exists()) {
            throw ValidationException::withMessages([
                'data.treasury_id' => 'الخزينة المحددة غير موجودة.',
            ]);
        }

        Validator::make(
            ['data' => ['voucher_date' => $data['voucher_date'] ?? null]],
            ['data.voucher_date' => ['required', 'date']],
            [
                'data.voucher_date.required' => 'تاريخ سند الصرف مطلوب.',
                'data.voucher_date.date' => 'تاريخ سند الصرف غير صحيح.',
            ],
        )->validate();

        if (! $paymentMethod) {
            throw ValidationException::withMessages([
                'data.payment_method' => 'طريقة الدفع المحددة غير صحيحة.',
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'data.amount' => 'يجب أن تكون قيمة السند أكبر من صفر.',
            ]);
        }

        if ($invoiceId <= 0) {
            throw ValidationException::withMessages([
                'data.purchase_invoice_id' => 'يجب اختيار فاتورة شراء.',
            ]);
        }
    }

    private function persistAllocation(
        SupplierPaymentVoucher $voucher,
        int $invoiceId,
    ): void {
        $invoice = PurchaseInvoice::query()
            ->with('items')
            ->lockForUpdate()
            ->find($invoiceId);

        if (! $invoice) {
            throw ValidationException::withMessages([
                'data.purchase_invoice_id' => 'فاتورة الشراء المحددة غير موجودة.',
            ]);
        }

        if ((int) $invoice->supplier_id !== (int) $voucher->supplier_id) {
            throw ValidationException::withMessages([
                'data.purchase_invoice_id' => 'الفاتورة المحددة لا تخص هذا المورد.',
            ]);
        }

        $remaining = $invoice->remainingAmount();
        $amount = (float) $voucher->amount;

        if ($remaining <= 0) {
            throw ValidationException::withMessages([
                'data.purchase_invoice_id' => 'فاتورة الشراء المحددة مدفوعة بالكامل.',
            ]);
        }

        if ($amount > $remaining) {
            throw ValidationException::withMessages([
                'data.amount' => 'قيمة السند أكبر من المتبقي على الفاتورة.',
            ]);
        }

        $voucher->allocations()->create([
            'purchase_invoice_id' => $invoice->getKey(),
            'amount' => round($amount, 2),
        ]);
    }

    private function applyFinancialEffect(SupplierPaymentVoucher $voucher): void
    {
        $voucher->loadMissing(['supplier', 'treasury']);

        $this->treasuryTransactionService->replaceForSource(
            $voucher->treasury,
            $voucher,
            $voucher->voucher_date,
            TreasuryTransaction::TYPE_PAYMENT,
            (float) $voucher->amount,
            TreasuryTransaction::DIRECTION_CREDIT,
            $voucher->document_number,
            "صرف دفعة للمورد {$voucher->supplier->name}",
            $voucher->created_by,
        );

        $this->partyTransactionService->replaceDocumentTransaction(
            $voucher->supplier,
            PartyTransaction::TYPE_SUPPLIER_PAYMENT,
            $voucher,
            $voucher->voucher_date,
            (float) $voucher->amount,
            0,
            $voucher->document_number,
            "سداد دفعة للمورد بموجب سند صرف {$voucher->document_number}",
        );
    }

    private function removeFinancialEffect(SupplierPaymentVoucher $voucher): void
    {
        $this->treasuryTransactionService->deleteForSource($voucher);
        $this->partyTransactionService->deleteDocumentTransaction($voucher);
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'supplier',
            'treasury',
            'createdBy',
            'allocations.purchaseInvoice',
        ];
    }
}
