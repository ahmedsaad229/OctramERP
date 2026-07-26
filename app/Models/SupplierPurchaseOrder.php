<?php

namespace App\Models;

use App\Enums\PaymentType;
use App\Enums\TaxType;
use App\Models\Concerns\ProtectsDocumentDeletion;
use App\Services\DocumentNumberService;
use App\Support\Octram\Traits\HasCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SupplierPurchaseOrder extends BaseModel
{
    use HasCode;
    use ProtectsDocumentDeletion;

    protected static string $documentType = DocumentNumberService::SUPPLIER_PURCHASE_ORDER;

    protected $fillable = [
        'code', 'order_date', 'supplier_id', 'purchase_request_id', 'warehouse_id',
        'expected_delivery_date', 'payment_type', 'due_date', 'supplier_reference',
        'subtotal', 'discount_amount', 'tax_type', 'tax_amount', 'total', 'notes', 'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'due_date' => 'date',
        'payment_type' => PaymentType::class,
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_type' => TaxType::class,
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierPurchaseOrderItem::class)->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function purchaseInvoiceItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            PurchaseInvoiceItem::class,
            SupplierPurchaseOrderItem::class,
        );
    }

    public function orderedQuantity(): float
    {
        return (float) $this->items()->sum('ordered_quantity');
    }

    public function invoicedQuantity(): float
    {
        return (float) $this->purchaseInvoiceItems()->sum('quantity');
    }

    public function remainingToInvoiceQuantity(): float
    {
        return max(0, $this->orderedQuantity() - $this->invoicedQuantity());
    }

    public function invoiceConversionStatus(): string
    {
        if ($this->invoicedQuantity() <= 0) {
            return 'not_invoiced';
        }

        return $this->remainingToInvoiceQuantity() > 0 ? 'partially_invoiced' : 'fully_invoiced';
    }

    public function invoiceConversionStatusLabel(): string
    {
        return match ($this->invoiceConversionStatus()) {
            'partially_invoiced' => 'تمت الفوترة جزئيًا',
            'fully_invoiced' => 'تمت الفوترة بالكامل',
            default => 'لم تتم الفوترة',
        };
    }

    public function calculateSubtotal(): float
    {
        return round((float) $this->items->sum('line_total'), 2);
    }

    public function calculateTotal(): float
    {
        return round($this->calculateSubtotal() - (float) $this->discount_amount + (float) $this->tax_amount, 2);
    }
}
