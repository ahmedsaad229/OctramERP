<?php

namespace App\Models;

use App\Models\Concerns\ProtectsDocumentDeletion;
use App\Support\Octram\Traits\HasCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends BaseModel
{
    use HasCode;
    use ProtectsDocumentDeletion;

    protected static string $codePrefix = 'ITM';

    protected $fillable = [
        'code',
        'sku',
        'barcode',
        'name',
        'name_en',
        'is_stock_item',
        'category_id',
        'unit_id',
        'purchase_price',
        'sale_price',
        'minimum_stock',
        'reorder_level',
        'allow_negative_stock',
        'active',
        'description',
    ];

    protected $casts = [
        'is_stock_item' => 'boolean',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'reorder_level' => 'decimal:2',
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

    public function isStockItem(): bool
    {
        return (bool) ($this->is_stock_item ?? true);
    }

    public function isNonStockItem(): bool
    {
        return ! $this->isStockItem();
    }
}
