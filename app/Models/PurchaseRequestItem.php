<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequestItem extends BaseModel
{
    protected $fillable = [
        'purchase_request_id', 'item_id', 'unit_id', 'requested_quantity', 'notes', 'sort_order',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(SupplierPurchaseOrderItem::class);
    }

    public function orderedQuantity(?int $excludingPurchaseOrderId = null): float
    {
        return (float) $this->purchaseOrderItems()
            ->when(
                $excludingPurchaseOrderId,
                fn ($query) => $query->whereHas(
                    'supplierPurchaseOrder',
                    fn ($query) => $query->whereKeyNot($excludingPurchaseOrderId),
                ),
            )
            ->sum('ordered_quantity');
    }

    public function remainingToOrderQuantity(?int $excludingPurchaseOrderId = null): float
    {
        return max(0, (float) $this->requested_quantity - $this->orderedQuantity($excludingPurchaseOrderId));
    }

    public function isFullyOrdered(): bool
    {
        return $this->remainingToOrderQuantity() <= 0;
    }
}
