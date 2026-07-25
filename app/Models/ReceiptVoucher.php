<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiptVoucher extends BaseModel
{
    protected $fillable = [
        'document_number',
        'treasury_id',
        'customer_id',
        'date',
        'amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
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
