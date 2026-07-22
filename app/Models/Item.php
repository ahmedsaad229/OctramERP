<?php

namespace App\Models;

use App\Support\Octram\Traits\HasCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends BaseModel
{
    use HasCode;

    protected static string $codePrefix = 'ITM';

    protected $fillable = [
        'code',
        'sku',
        'barcode',
        'name',
        'name_en',
        'category_id',
        'unit_id',
        'purchase_price',
        'sale_price',
        'minimum_stock',
        'allow_negative_stock',
        'active',
        'description',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'allow_negative_stock' => 'boolean',
        'active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}