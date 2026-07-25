<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends BaseModel
{
    protected $fillable = [
        'sales_invoice_id',
        'item_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
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
}
