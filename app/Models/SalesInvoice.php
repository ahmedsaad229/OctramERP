<?php

namespace App\Models;

use App\Enums\PaymentType;
use App\Enums\TaxType;
use App\Models\Concerns\HasInformationalPaymentTerms;
use App\Models\Concerns\ProtectsDocumentDeletion;
use App\Services\DocumentNumberService;
use App\Services\DocumentTaxCalculator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends BaseModel
{
    use HasInformationalPaymentTerms;
    use ProtectsDocumentDeletion;

    protected $fillable = [
        'document_number',
        'electronic_invoice_number',
        'invoice_date',
        'customer_id',
        'warehouse_id',
        'sales_quotation_id',
        'customer_purchase_order_id',
        'payment_type',
        'due_date',
        'discount_amount',
        'tax_type',
        'tax_amount',
        'service_tax_discount_enabled',
        'service_tax_discount_amount',
        'one_percent_discount_enabled',
        'one_percent_discount_amount',
        'notes',
    ];

    protected $casts = [
        'electronic_invoice_number' => 'integer',
        'invoice_date' => 'date',
        'payment_type' => PaymentType::class,
        'due_date' => 'date',
        'discount_amount' => 'decimal:2',
        'tax_type' => TaxType::class,
        'tax_amount' => 'decimal:2',
        'service_tax_discount_enabled' => 'boolean',
        'service_tax_discount_amount' => 'decimal:2',
        'one_percent_discount_enabled' => 'boolean',
        'one_percent_discount_amount' => 'decimal:2',
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

    public function salesQuotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class);
    }

    public function customerPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerPurchaseOrder::class);
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
        $subtotal = (float) ($this->relationLoaded('items')
            ? $this->items->sum('line_total')
            : $this->items()->sum('line_total'));

        return round(
            max(
                0,
                max(0, $subtotal - (float) $this->discount_amount)
                + (float) $this->tax_amount
                - (float) $this->service_tax_discount_amount
                - (float) $this->one_percent_discount_amount
            ),
            2
        );
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
