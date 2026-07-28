<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerPurchaseOrderExecution extends BaseModel
{
    protected $fillable = ['customer_purchase_order_id', 'customer_purchase_order_item_id', 'source_type', 'source_id', 'source_item_id', 'executed_quantity', 'execution_date'];

    protected $casts = ['executed_quantity' => 'decimal:2', 'execution_date' => 'date'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerPurchaseOrder::class, 'customer_purchase_order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(CustomerPurchaseOrderItem::class, 'customer_purchase_order_item_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
