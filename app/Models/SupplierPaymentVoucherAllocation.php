<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPaymentVoucherAllocation extends BaseModel
{
    protected $fillable = [
        'supplier_payment_voucher_id',
        'purchase_invoice_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function supplierPaymentVoucher(): BelongsTo
    {
        return $this->belongsTo(SupplierPaymentVoucher::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }
}
