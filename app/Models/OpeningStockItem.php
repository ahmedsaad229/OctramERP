<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningStockItem extends BaseModel
{
    protected $fillable = [
        'opening_stock_voucher_id',
        'item_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_cost'  => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * مستند أول المدة
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(OpeningStockVoucher::class, 'opening_stock_voucher_id');
    }

    /**
     * الصنف
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}