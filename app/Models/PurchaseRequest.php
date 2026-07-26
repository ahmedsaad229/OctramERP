<?php

namespace App\Models;

use App\Models\Concerns\ProtectsDocumentDeletion;
use App\Services\DocumentNumberService;
use App\Support\Octram\Traits\HasCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends BaseModel
{
    use HasCode;
    use ProtectsDocumentDeletion;

    public const STATUS_NOT_ORDERED = 'not_ordered';

    public const STATUS_PARTIALLY_ORDERED = 'partially_ordered';

    public const STATUS_FULLY_ORDERED = 'fully_ordered';

    protected static string $documentType = DocumentNumberService::PURCHASE_REQUEST;

    protected $fillable = [
        'code', 'request_date', 'required_date', 'warehouse_id', 'requested_by',
        'department', 'purpose', 'notes', 'created_by',
    ];

    protected $casts = [
        'request_date' => 'date',
        'required_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class)->orderBy('sort_order');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(SupplierPurchaseOrder::class);
    }

    public function totalRequestedQuantity(): float
    {
        return (float) $this->items->sum('requested_quantity');
    }

    public function orderedQuantity(): float
    {
        return (float) SupplierPurchaseOrderItem::query()
            ->whereHas('purchaseRequestItem', fn ($query) => $query->where('purchase_request_id', $this->getKey()))
            ->sum('ordered_quantity');
    }

    public function remainingQuantity(): float
    {
        return max(0, $this->totalRequestedQuantity() - $this->orderedQuantity());
    }

    public function procurementStatus(): string
    {
        $ordered = $this->orderedQuantity();

        if ($ordered <= 0) {
            return self::STATUS_NOT_ORDERED;
        }

        return $this->remainingQuantity() > 0
            ? self::STATUS_PARTIALLY_ORDERED
            : self::STATUS_FULLY_ORDERED;
    }

    public function procurementStatusLabel(): string
    {
        return match ($this->procurementStatus()) {
            self::STATUS_PARTIALLY_ORDERED => 'تم التوريد جزئيًا',
            self::STATUS_FULLY_ORDERED => 'تم إصدار أمر توريد بالكامل',
            default => 'لم يتم إصدار أمر توريد',
        };
    }
}
