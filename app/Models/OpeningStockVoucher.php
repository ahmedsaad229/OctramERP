<?php

namespace App\Models;

use App\Support\Octram\Traits\HasCode;
use App\Services\DocumentNumberService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpeningStockVoucher extends BaseModel
{
    use HasCode;

    protected static string $codePrefix = 'OSV';

    protected static string $documentType = DocumentNumberService::OPENING_STOCK;

    protected $fillable = [
        'code',
        'voucher_date',
        'warehouse_id',
        'notes',
        'posted',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'posted' => 'boolean',
    ];

    /**
     * Warehouse
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Voucher Items
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            OpeningStockItem::class,
            'opening_stock_voucher_id'
        );
    }

    /**
     * Total Quantity
     */
    public function getTotalQuantityAttribute(): float
    {
        return (float) $this->items()->sum('quantity');
    }

    /**
     * Total Cost
     */
    public function getTotalCostAttribute(): float
    {
        return (float) $this->items()->sum('total_cost');
    }

    /**
     * Posted ?
     */
    public function isPosted(): bool
    {
        return (bool) $this->posted;
    }
}
