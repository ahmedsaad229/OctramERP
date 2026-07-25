<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptVoucherAllocation extends BaseModel
{
    protected $fillable = [
        'receipt_voucher_id',
        'sales_invoice_id',
        'electronic_invoice_number',
        'amount',
    ];

    protected $casts = [
        'electronic_invoice_number' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function receiptVoucher(): BelongsTo
    {
        return $this->belongsTo(ReceiptVoucher::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }
}
