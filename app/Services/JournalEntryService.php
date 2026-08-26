<?php

namespace App\Services;

use App\Enums\PaymentType;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\ReceiptVoucher;
use App\Models\SalesInvoice;
use App\Models\SupplierPaymentVoucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{
    public function __construct(private readonly AccountingAccountResolver $resolver) {}

    public function postSalesInvoice(SalesInvoice $invoice): void
    {
        $invoice->loadMissing('items');
        $total = round($invoice->totalAmount(), 2);
        $tax = round((float) $invoice->tax_amount, 2);
        $serviceTaxDiscount = round(
            (float) $invoice->service_tax_discount_amount,
            2
        );

        $onePercentDiscount = round(
            (float) $invoice->one_percent_discount_amount,
            2
        );

        /*
         * المبيعات قبل VAT وقبل خصم ضريبة الخدمات.
         */
        $revenue = round(
            max(
                0,
                $total
                + $serviceTaxDiscount
                + $onePercentDiscount
                - $tax
            ),
            2
        );
        $paymentType = $invoice->payment_type instanceof PaymentType
            ? $invoice->payment_type->value : (string) $invoice->payment_type;

        $this->replace($invoice, $invoice->invoice_date, $invoice->document_number,
            "فاتورة بيع {$invoice->document_number}", [
                [$paymentType === PaymentType::Cash->value ? AccountingAccountResolver::CASH : AccountingAccountResolver::CUSTOMERS, $total, 0, 'صافي المستحق'],
                [AccountingAccountResolver::SERVICE_TAX_DISCOUNT, $serviceTaxDiscount, 0, 'خصم ضريبة خدمات 3%'],
                [AccountingAccountResolver::ONE_PERCENT_DISCOUNT, $onePercentDiscount, 0, 'خصم وإضافة 1%'],
                [AccountingAccountResolver::SALES, 0, $revenue, 'صافي المبيعات'],
                [AccountingAccountResolver::OUTPUT_VAT, 0, $tax, 'ضريبة المخرجات'],
            ]);
    }

    public function postPurchaseInvoice(PurchaseInvoice $invoice): void
    {
        $invoice->loadMissing('items');
        $total = round($invoice->totalAmount(), 2);
        $tax = round((float) $invoice->tax_amount, 2);
        $purchases = round(max(0, $total - $tax), 2);
        $paymentType = $invoice->payment_type instanceof PaymentType
            ? $invoice->payment_type->value : (string) $invoice->payment_type;

        $this->replace($invoice, $invoice->invoice_date, $invoice->code,
            "فاتورة شراء {$invoice->code}", [
                [AccountingAccountResolver::PURCHASES, $purchases, 0, 'صافي المشتريات'],
                [AccountingAccountResolver::INPUT_VAT, $tax, 0, 'ضريبة المدخلات'],
                [$paymentType === PaymentType::Cash->value ? AccountingAccountResolver::CASH : AccountingAccountResolver::SUPPLIERS, 0, $total, 'إجمالي الفاتورة'],
            ]);
    }

    public function postReceiptVoucher(ReceiptVoucher $voucher): void
    {
        $creditAccount = $voucher->isCustomerReceipt()
            ? AccountingAccountResolver::CUSTOMERS
            : AccountingAccountResolver::OTHER_INCOME;

        $this->replace($voucher, $voucher->date, $voucher->document_number,
            "سند قبض {$voucher->document_number}", [
                [AccountingAccountResolver::CASH, (float) $voucher->amount, 0, 'الخزينة'],
                [$creditAccount, 0, (float) $voucher->amount, $voucher->receivedFromName()],
            ]);
    }

    public function postSupplierPaymentVoucher(SupplierPaymentVoucher $voucher): void
    {
        /*
         * سند صرف مورد:
         * مدين = الموردين
         * دائن = الخزينة
         */
        if ($voucher->isSupplierPayment()) {
            $this->replace(
                $voucher,
                $voucher->voucher_date,
                $voucher->document_number,
                "سند صرف {$voucher->document_number}",
                [
                    [
                        AccountingAccountResolver::SUPPLIERS,
                        (float) $voucher->amount,
                        0,
                        $voucher->paidToName(),
                    ],
                    [
                        AccountingAccountResolver::CASH,
                        0,
                        (float) $voucher->amount,
                        'الخزينة',
                    ],
                ]
            );

            return;
        }

        /*
         * الصرف العام:
         * مدين = الحساب المختار من دليل الحسابات
         * دائن = الخزينة
         */
        if (! $voucher->expense_account_id) {
            throw ValidationException::withMessages([
                'expense_account_id' =>
                    'يجب اختيار حساب سبب الصرف من دليل الحسابات.',
            ]);
        }

        $this->replaceWithDebitAccountId(
            $voucher,
            $voucher->voucher_date,
            $voucher->document_number,
            "سند صرف {$voucher->document_number}",
            (int) $voucher->expense_account_id,
            (float) $voucher->amount,
            $voucher->paidToName(),
        );
    }

    public function deleteForSource(Model $source): void
    {
        $documentNumber = null;
        $automaticDescription = null;

        if ($source instanceof SalesInvoice) {
            $documentNumber = $source->document_number;
            $automaticDescription = "فاتورة بيع {$source->document_number}";
        } elseif ($source instanceof PurchaseInvoice) {
            $documentNumber = $source->code;
            $automaticDescription = "فاتورة شراء {$source->code}";
        }

        JournalEntry::query()
            ->where(function ($query) use ($source, $documentNumber, $automaticDescription): void {
                $query->where(function ($query) use ($source): void {
                    $query
                        ->where('source_type', $source->getMorphClass())
                        ->where('source_id', $source->getKey());
                });

                // Fallback للقيود القديمة التي قد يكون ربط source_type/source_id فيها غير سليم.
                // الشرط مقيد برقم المستند + الوصف الأوتوماتيكي المطابق تماماً،
                // حتى لا يتم حذف قيد يدوي يحمل نفس الرقم بالصدفة.
                if (filled($documentNumber) && filled($automaticDescription)) {
                    $query->orWhere(function ($query) use ($documentNumber, $automaticDescription): void {
                        $query
                            ->where('document_number', $documentNumber)
                            ->where('description', $automaticDescription);
                    });
                }
            })
            ->delete();
    }

    private function replaceWithDebitAccountId(
        Model $source,
        mixed $date,
        ?string $number,
        string $description,
        int $debitAccountId,
        float $amount,
        ?string $memo = null,
    ): void {
        DB::transaction(function () use (
            $source,
            $date,
            $number,
            $description,
            $debitAccountId,
            $amount,
            $memo
        ): void {
            $accounts = $this->resolver->ensureAccounts();

            $debitAccount = \App\Models\Account::query()
                ->posting()
                ->find($debitAccountId);

            if (! $debitAccount) {
                throw ValidationException::withMessages([
                    'expense_account_id' =>
                        'الحساب المختار غير موجود أو غير مسموح بالترحيل عليه.',
                ]);
            }

            $cashAccount = $accounts[
                AccountingAccountResolver::CASH
            ];

            $amount = round($amount, 2);

            $this->deleteForSource($source);

            $entry = JournalEntry::query()->create([
                'entry_date' => $date,
                'entry_type' => JournalEntry::TYPE_AUTOMATIC,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'document_number' => $number,
                'description' => $description,
                'created_by' => null,
            ]);

            $entry->lines()->createMany([
                [
                    'account_id' => $debitAccount->getKey(),
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => $memo,
                ],
                [
                    'account_id' => $cashAccount->getKey(),
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => 'الخزينة',
                ],
            ]);
        });
    }
    /** @param array<int, array{0:string,1:float|int,2:float|int,3?:string}> $lines */
    private function replace(Model $source, mixed $date, ?string $number, string $description, array $lines): void
    {
        DB::transaction(function () use ($source, $date, $number, $description, $lines): void {
            $accounts = $this->resolver->ensureAccounts();
            $normalized = [];
            $totalDebit = 0.0;
            $totalCredit = 0.0;

            foreach ($lines as [$code, $debit, $credit, $memo]) {
                $debit = round((float) $debit, 2);
                $credit = round((float) $credit, 2);
                if ($debit <= 0 && $credit <= 0) continue;
                $normalized[] = [
                    'account_id' => $accounts[$code]->getKey(),
                    'debit' => $debit,
                    'credit' => $credit,
                    'memo' => $memo ?? null,
                ];
                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            if (abs($totalDebit - $totalCredit) > 0.009) {
                throw ValidationException::withMessages(['accounting' => 'القيد المحاسبي غير متزن.']);
            }

            $this->deleteForSource($source);
            $entry = JournalEntry::query()->create([
                'entry_date' => $date,
                'entry_type' => JournalEntry::TYPE_AUTOMATIC,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'document_number' => $number,
                'description' => $description,
                'created_by' => null,
            ]);
            $entry->lines()->createMany($normalized);
        });
    }
}
