<?php

namespace App\Models;

use App\Enums\PaymentType;
use App\Models\Concerns\HasInformationalPaymentTerms;
use App\Services\DocumentNumberService;
use App\Support\Octram\Traits\HasCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends BaseModel
{
    use HasCode;
    use HasInformationalPaymentTerms;

    protected static string $codePrefix = 'PIV';

    protected static string $documentType = DocumentNumberService::PURCHASE_INVOICE;

    protected $fillable = [
        'code', 'supplier_id', 'invoice_number', 'invoice_date', 'warehouse_id', 'payment_type', 'due_date', 'notes', 'posted',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'payment_type' => PaymentType::class,
        'due_date' => 'date',
        'posted' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function totalAmount(): float
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return (float) $items->sum(
            fn (PurchaseInvoiceItem $item): float => (float) $item->quantity * (float) $item->unit_cost,
        );
    }
}
