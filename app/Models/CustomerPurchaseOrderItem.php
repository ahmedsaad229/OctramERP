<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPurchaseOrderItem extends BaseModel
{
    protected $fillable = [
        'item_id', 'description', 'unit_id', 'ordered_quantity', 'executed_quantity',
        'remaining_quantity', 'unit_price', 'discount_amount', 'tax_exempt', 'tax_rate',
        'line_subtotal', 'line_tax', 'line_total', 'notes', 'sort_order',
    ];

    protected $casts = [
        'ordered_quantity' => 'decimal:2',
        'executed_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_exempt' => 'boolean',
        'tax_rate' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'line_tax' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerPurchaseOrder::class, 'customer_purchase_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(CustomerPurchaseOrderExecution::class);
    }
}
