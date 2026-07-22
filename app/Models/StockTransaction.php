<?php

namespace App\Models;

class StockTransaction extends BaseModel
{
    public const TYPE_OPENING = 'opening';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SALE = 'sale';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'transaction_type',
        'quantity',
        'unit_cost',
        'transaction_date',
        'reference_no',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public static function types(): array
    {
        return [
            self::TYPE_OPENING => 'رصيد أول المدة',
            self::TYPE_PURCHASE => 'شراء',
            self::TYPE_SALE => 'بيع',
            self::TYPE_TRANSFER_IN => 'تحويل وارد',
            self::TYPE_TRANSFER_OUT => 'تحويل صادر',
            self::TYPE_ADJUSTMENT => 'تسوية مخزنية',
        ];
    }
}