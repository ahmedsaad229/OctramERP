<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends BaseModel
{
    protected $fillable = [
        'goods_receipt_voucher_id',
        'item_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptVoucher::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
