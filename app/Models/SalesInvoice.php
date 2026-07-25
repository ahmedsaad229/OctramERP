<?php

namespace App\Models;

use App\Services\DocumentNumberService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends BaseModel
{
    protected $fillable = [
        'document_number',
        'electronic_invoice_number',
        'invoice_date',
        'customer_id',
        'warehouse_id',
        'notes',
    ];

    protected $casts = [
        'electronic_invoice_number' => 'integer',
        'invoice_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesInvoice $invoice): void {
            if (blank($invoice->document_number)) {
                $invoice->document_number = app(DocumentNumberService::class)
                    ->generate(DocumentNumberService::SALES_INVOICE);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function receiptAllocations(): HasMany
    {
        return $this->hasMany(ReceiptVoucherAllocation::class);
    }

    public function totalAmount(): float
    {
        return (float) ($this->relationLoaded('items')
            ? $this->items->sum('line_total')
            : $this->items()->sum('line_total'));
    }

    public function paidAmount(?int $excludingReceiptVoucherId = null): float
    {
        return (float) $this->receiptAllocations()
            ->when(
                $excludingReceiptVoucherId,
                fn ($query) => $query->where('receipt_voucher_id', '!=', $excludingReceiptVoucherId),
            )
            ->sum('amount');
    }

    public function remainingAmount(?int $excludingReceiptVoucherId = null): float
    {
        return max(0, $this->totalAmount() - $this->paidAmount($excludingReceiptVoucherId));
    }

    public function previouslyPaidBeforeReceipt(ReceiptVoucher $receiptVoucher): float
    {
        if ($this->relationLoaded('receiptAllocations')) {
            return (float) $this->receiptAllocations
                ->filter(function (ReceiptVoucherAllocation $allocation) use ($receiptVoucher): bool {
                    $allocatedReceipt = $allocation->receiptVoucher;

                    if (! $allocatedReceipt || $allocatedReceipt->is($receiptVoucher)) {
                        return false;
                    }

                    return $allocatedReceipt->date->lt($receiptVoucher->date)
                        || (
                            $allocatedReceipt->date->equalTo($receiptVoucher->date)
                            && $allocatedReceipt->getKey() < $receiptVoucher->getKey()
                        );
                })
                ->sum('amount');
        }

        return (float) $this->receiptAllocations()
            ->whereHas('receiptVoucher', fn ($query) => $query
                ->where('date', '<', $receiptVoucher->date)
                ->orWhere(function ($query) use ($receiptVoucher): void {
                    $query
                        ->whereDate('date', $receiptVoucher->date)
                        ->where('id', '<', $receiptVoucher->getKey());
                }))
            ->sum('amount');
    }

    public function remainingBeforeReceipt(ReceiptVoucher $receiptVoucher): float
    {
        return max(0, $this->totalAmount() - $this->previouslyPaidBeforeReceipt($receiptVoucher));
    }

    public function paymentStatus(): string
    {
        $paidAmount = $this->paidAmount();
        $remainingAmount = max(0, $this->totalAmount() - $paidAmount);

        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        return $remainingAmount > 0 ? 'partially_paid' : 'paid';
    }
}
