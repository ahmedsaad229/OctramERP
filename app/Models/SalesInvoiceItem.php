<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends BaseModel
{
    protected $fillable = [
        'sales_invoice_id',
        'item_id',
        'sales_quotation_item_id',
        'customer_purchase_order_item_id',
        'unit_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_exempt',
        'tax_amount',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_exempt' => 'boolean',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function salesQuotationItem(): BelongsTo
    {
        return $this->belongsTo(SalesQuotationItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
