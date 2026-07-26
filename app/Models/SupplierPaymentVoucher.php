<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\ProtectsDocumentDeletion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPaymentVoucher extends BaseModel
{
    use ProtectsDocumentDeletion;

    protected $fillable = [
        'document_number',
        'voucher_date',
        'supplier_id',
        'treasury_id',
        'payment_method',
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
