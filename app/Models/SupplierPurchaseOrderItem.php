<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPurchaseOrderItem extends BaseModel
{
    protected $fillable = [
        'supplier_purchase_order_id', 'purchase_request_item_id', 'item_id', 'unit_id',
        'ordered_quantity', 'unit_price', 'line_total', 'notes', 'sort_order',
    ];

    protected $casts = [
        'ordered_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function supplierPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchaseOrder::class);
    }

    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseInvoiceItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function invoicedQuantity(?PurchaseInvoice $excludingInvoice = null): float
    {
        return (float) $this->purchaseInvoiceItems()
            ->when(
                $excludingInvoice,
                fn ($query) => $query->where('purchase_invoice_id', '!=', $excludingInvoice->getKey()),
            )
            ->sum('quantity');
    }

    public function previouslyInvoicedQuantityBefore(?PurchaseInvoice $invoice = null): float
    {
        return $this->invoicedQuantity($invoice);
    }

    public function remainingToInvoiceQuantity(?PurchaseInvoice $invoice = null): float
    {
        return max(0, (float) $this->ordered_quantity - $this->invoicedQuantity($invoice));
    }

    public function isFullyInvoiced(): bool
    {
        return $this->remainingToInvoiceQuantity() <= 0;
    }
}
