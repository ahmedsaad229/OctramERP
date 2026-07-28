<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\ProtectsDocumentDeletion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiptVoucher extends BaseModel
{
    use ProtectsDocumentDeletion;

    public const TYPE_CUSTOMER = 'customer';

    public const TYPE_GENERAL = 'general';

    public const REASON_CAPITAL = 'capital';

    public const REASON_LOAN_REPAYMENT = 'loan_repayment';

    public const REASON_ADVANCE_REPAYMENT = 'advance_repayment';

    public const REASON_MISCELLANEOUS_INCOME = 'miscellaneous_income';

    public const REASON_BANK_WITHDRAWAL = 'bank_withdrawal';

    public const REASON_OTHER = 'other';

    protected $fillable = [
        'document_number',
        'receipt_type',
        'treasury_id',
        'customer_id',
        'date',
        'amount',
        'payment_method',
        'receipt_reason',
        'payer_name',
        'reference_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'payment_method' => PaymentMethod::class,
    ];

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ReceiptVoucherAllocation::class);
    }

    public function relatedSalesInvoice(): ?SalesInvoice
    {
        return $this->allocations->first()?->salesInvoice;
    }

    /** @return array<string, string> */
    public static function receiptTypeOptions(): array
    {
        return [
            self::TYPE_CUSTOMER => 'استلام من عميل',
            self::TYPE_GENERAL => 'استلام عام',
        ];
    }

    /** @return array<string, string> */
    public static function receiptReasonOptions(): array
    {
        return [
            self::REASON_CAPITAL => 'رأس مال',
            self::REASON_LOAN_REPAYMENT => 'رد قرض أو سلفة',
            self::REASON_ADVANCE_REPAYMENT => 'رد عهدة أو سلفة موظف',
            self::REASON_MISCELLANEOUS_INCOME => 'إيراد متنوع',
            self::REASON_BANK_WITHDRAWAL => 'سحب من البنك',
            self::REASON_OTHER => 'أخرى',
        ];
    }

    public function isCustomerReceipt(): bool
    {
        return ($this->receipt_type ?? self::TYPE_CUSTOMER) === self::TYPE_CUSTOMER;
    }

    public function isGeneralReceipt(): bool
    {
        return $this->receipt_type === self::TYPE_GENERAL;
    }

    public function getReceiptTypeLabelAttribute(): string
    {
        return self::receiptTypeOptions()[$this->receipt_type ?? self::TYPE_CUSTOMER] ?? 'استلام نقدي';
    }

    public function getReceiptReasonLabelAttribute(): ?string
    {
        return self::receiptReasonOptions()[$this->receipt_reason] ?? null;
    }

    public function receivedFromName(): string
    {
        return $this->isCustomerReceipt()
            ? ($this->customer?->name ?? '—')
            : ($this->payer_name ?: ($this->receipt_reason_label ?: '—'));
    }

    /**
     * @return array{previously_paid: float, remaining_after_receipt: float}
     */
    public function paymentSummaryBefore(): array
    {
        $invoice = $this->relatedSalesInvoice();

        if (! $invoice) {
            return [
                'previously_paid' => 0.0,
                'remaining_after_receipt' => 0.0,
            ];
        }

        $previouslyPaid = $invoice->previouslyPaidBeforeReceipt($this);

        return [
            'previously_paid' => $previouslyPaid,
            'remaining_after_receipt' => max(
                0,
                $invoice->totalAmount() - $previouslyPaid - (float) $this->amount,
            ),
        ];
    }
}
