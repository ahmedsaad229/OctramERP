<?php

namespace App\Models;

use App\Enums\TaxType;
use App\Models\Concerns\ProtectsDocumentDeletion;
use App\Services\DocumentNumberService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesQuotation extends BaseModel
{
    use ProtectsDocumentDeletion;

    public const STATUS_NOT_CONVERTED = 'not_converted';

    public const STATUS_PARTIALLY_CONVERTED = 'partially_converted';

    public const STATUS_FULLY_CONVERTED = 'fully_converted';

    protected $fillable = [
        'quotation_number', 'quotation_date', 'valid_until', 'customer_id', 'warehouse_id',
        'tax_type', 'subtotal', 'discount_amount', 'tax_amount', 'total_amount',
        'notes', 'terms_and_conditions', 'created_by',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'tax_type' => TaxType::class,
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $quotation): void {
            $quotation->quotation_number ??= app(DocumentNumberService::class)
                ->generate(DocumentNumberService::SALES_QUOTATION);
            $quotation->created_by ??= auth()->id();
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesQuotationItem::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function isPartiallyConverted(): bool
    {
        $invoiced = (float) $this->items->sum(fn (SalesQuotationItem $item): float => $item->invoicedQuantity());

        return $invoiced > 0 && ! $this->isFullyConverted();
    }

    public function isFullyConverted(): bool
    {
        return $this->items->isNotEmpty()
            && $this->items->every(fn (SalesQuotationItem $item): bool => $item->remainingQuantity() <= 0);
    }

    public function conversionStatus(): string
    {
        return $this->isFullyConverted()
            ? self::STATUS_FULLY_CONVERTED
            : ($this->isPartiallyConverted() ? self::STATUS_PARTIALLY_CONVERTED : self::STATUS_NOT_CONVERTED);
    }

    public function isExpired(): bool
    {
        return $this->valid_until?->isBefore(today()) ?? false;
    }

    public function expiryStatus(): string
    {
        if (! $this->valid_until) {
            return 'open';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        return $this->valid_until->diffInDays(today()) <= 3 ? 'expiring' : 'active';
    }

    public function expiryLabel(): string
    {
        if (! $this->valid_until) {
            return 'بدون تاريخ انتهاء';
        }

        $days = today()->diffInDays($this->valid_until, false);

        if ($days < 0) {
            return 'منتهي منذ '.abs($days).' يوم';
        }

        if ($days === 0) {
            return 'ينتهي اليوم';
        }

        return 'متبقي '.$days.' يوم';
    }
}
