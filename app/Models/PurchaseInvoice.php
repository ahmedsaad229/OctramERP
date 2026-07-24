<?php

namespace App\Models;

use App\Support\Octram\Traits\HasCode;
use App\Services\DocumentNumberService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends BaseModel
{
    use HasCode;

    protected static string $codePrefix = 'PIV';

    protected static string $documentType = DocumentNumberService::PURCHASE_INVOICE;

    protected $fillable = [
        'code', 'supplier_id', 'invoice_number', 'invoice_date', 'warehouse_id', 'notes', 'posted',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'posted' => 'boolean',
    ];

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseInvoiceItem::class); }
}
