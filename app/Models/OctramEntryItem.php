<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OctramEntryItem extends Model
{
    protected $fillable = [
        'octram_entry_id',
        'item_id',
        'item_name',
        'price_before_tax',
        'tax_amount',
        'price_including_tax',
    ];

    protected $casts = [
        'price_before_tax' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'price_including_tax' => 'decimal:2',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(OctramEntry::class, 'octram_entry_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}