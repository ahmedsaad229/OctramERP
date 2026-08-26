<?php

namespace App\Models;

use App\Models\Concerns\ProtectsDocumentDeletion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPurchaseOrder extends BaseModel
{
    use ProtectsDocumentDeletion;

    public const STATUS_NEW = 'new';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_PARTIAL = 'partially_completed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'document_number',
        'customer_order_number',
        'customer_id',
        'sales_quotation_id',
        'order_date',
        'received_date',
        'required_delivery_date',
        'actual_completion_date',
        'delivery_location',
        'project_name',
        'contact_person',
        'status',
        'execution_percentage',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'received_date' => 'date',
        'required_delivery_date' => 'date',
        'actual_completion_date' => 'date',
        'execution_percentage' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesQuotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerPurchaseOrderItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CustomerPurchaseOrderAttachment::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(CustomerPurchaseOrderFollowUp::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(CustomerPurchaseOrderExecution::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEW => 'جديد',
            self::STATUS_IN_PROGRESS => 'قيد التنفيذ',
            self::STATUS_PARTIAL => 'منفذ جزئيًا',
            self::STATUS_COMPLETED => 'منفذ بالكامل',
            self::STATUS_SUSPENDED => 'متوقف',
            self::STATUS_CANCELLED => 'ملغي',
        ];
    }

    public function isDelayed(): bool
    {
        return $this->required_delivery_date?->isPast()
            && ! in_array($this->status, [
                self::STATUS_COMPLETED,
                self::STATUS_CANCELLED,
            ], true);
    }
}