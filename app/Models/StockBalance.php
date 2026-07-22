<?php

namespace App\Models;

class StockBalance extends BaseModel
{
    protected $fillable = [
        'warehouse_id',
        'item_id',
        'quantity',
        'average_cost',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'average_cost' => 'decimal:2',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}