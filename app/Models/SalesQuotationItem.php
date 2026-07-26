<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesQuotationItem extends BaseModel
{
    protected $fillable = [
        'sales_quotation_id', 'item_id', 'unit_id', 'quantity', 'unit_price',
        'discount_amount', 'tax_amount', 'line_total', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'sales_quotation_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function salesInvoiceItems(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function invoicedQuantity(?int $excludingSalesInvoiceId = null): float
    {
        return (float) $this->salesInvoiceItems()
            ->when($excludingSalesInvoiceId, fn ($query) => $query->where('sales_invoice_id', '!=', $excludingSalesInvoiceId))
            ->sum('quantity');
    }

    public function remainingQuantity(?int $excludingSalesInvoiceId = null): float
    {
        return max(0, (float) $this->quantity - $this->invoicedQuantity($excludingSalesInvoiceId));
    }
}
