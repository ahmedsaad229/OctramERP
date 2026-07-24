<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends BaseModel
{
    protected $fillable = [
        'purchase_invoice_id', 'item_id', 'quantity', 'unit_cost', 'total_cost', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2', 'unit_cost' => 'decimal:2', 'total_cost' => 'decimal:2',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id'); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
}
