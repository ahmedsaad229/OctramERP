<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\ProtectsDocumentDeletion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPaymentVoucher extends BaseModel
{
    use ProtectsDocumentDeletion;

    public const TYPE_SUPPLIER = 'supplier';

    public const TYPE_GENERAL = 'general';

    public const REASON_OPERATING_EXPENSE = 'operating_expense';

    public const REASON_EMPLOYEE_ADVANCE = 'employee_advance';

    public const REASON_LOAN_PAYMENT = 'loan_payment';

    public const REASON_BANK_DEPOSIT = 'bank_deposit';

    public const REASON_MISCELLANEOUS_EXPENSE = 'miscellaneous_expense';

    public const REASON_DIRECT_PURCHASE = 'direct_purchase';

    public const REASON_OTHER = 'other';

    protected $fillable = [
        'document_number',
        'voucher_date',
        'payment_type',
        'supplier_id',
        'treasury_id',
        'payment_method',
        'payment_reason',
        'beneficiary_name',
        'reference_number',
        'amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'payment_method' => PaymentMethod::class,
        'amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentVoucherAllocation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function relatedPurchaseInvoice(): ?PurchaseInvoice
    {
        return $this->allocations->first()?->purchaseInvoice;
    }

    /** @return array<string, string> */
    public static function paymentTypeOptions(): array
    {
        return [
            self::TYPE_SUPPLIER => 'صرف لمورد',
            self::TYPE_GENERAL => 'صرف عام',
        ];
    }

    /** @return array<string, string> */
    public static function paymentReasonOptions(): array
    {
        return [
            self::REASON_OPERATING_EXPENSE => 'مصروف تشغيلي',
            self::REASON_EMPLOYEE_ADVANCE => 'سلفة موظف',
            self::REASON_LOAN_PAYMENT => 'سداد قرض أو التزام',
            self::REASON_BANK_DEPOSIT => 'إيداع في البنك',
            self::REASON_MISCELLANEOUS_EXPENSE => 'مصروف متنوع',
            self::REASON_DIRECT_PURCHASE => 'مشتريات نقدية عامة',
            self::REASON_OTHER => 'أخرى',
        ];
    }

    public function isSupplierPayment(): bool
    {
        return ($this->payment_type ?? self::TYPE_SUPPLIER) === self::TYPE_SUPPLIER;
    }

    public function isGeneralPayment(): bool
    {
        return $this->payment_type === self::TYPE_GENERAL;
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return self::paymentTypeOptions()[$this->payment_type ?? self::TYPE_SUPPLIER] ?? 'صرف نقدية';
    }

    public function getPaymentReasonLabelAttribute(): ?string
    {
        return self::paymentReasonOptions()[$this->payment_reason] ?? null;
    }

    public function paidToName(): string
    {
        return $this->isSupplierPayment()
            ? ($this->supplier?->name ?? '—')
            : ($this->beneficiary_name ?: ($this->payment_reason_label ?: '—'));
    }

    /**
     * @return array{invoice_total: float, previously_paid: float, current_payment: float, remaining_after_payment: float}
     */
    public function paymentSummaryBefore(): array
    {
        $invoice = $this->relatedPurchaseInvoice();

        if (! $invoice) {
            return [
                'invoice_total' => 0.0,
                'previously_paid' => 0.0,
                'current_payment' => (float) $this->amount,
                'remaining_after_payment' => 0.0,
            ];
        }

        $previouslyPaid = $invoice->previouslyPaidBeforeSupplierPayment($this);

        return [
            'invoice_total' => $invoice->totalAmount(),
            'previously_paid' => $previouslyPaid,
            'current_payment' => (float) $this->amount,
            'remaining_after_payment' => max(
                0,
                $invoice->totalAmount() - $previouslyPaid - (float) $this->amount,
            ),
        ];
    }
}
