<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OctramEntry extends Model
{
    protected static function booted(): void
    {
        static::saving(function (OctramEntry $entry): void {
            $quantity = max(
                0,
                (float) ($entry->purchase_quantity ?? 0)
            );

            $price = max(
                0,
                (float) ($entry->purchase_price ?? 0)
            );

            $base = round($quantity * $price, 2);

            $tax = (bool) $entry->purchase_tax_enabled
                ? round($base * 0.14, 2)
                : 0.0;

            $entry->purchase_tax = $tax;

            $entry->purchase_price_including_tax = round(
                $base + $tax,
                2
            );
        });
    }

    protected $fillable = [
        'entry_date',
        'purchase_date',
        'purchase_item_id',
        'purchase_quantity',
        'purchase_price',
        'purchase_tax_enabled',
        'purchase_tax',
        'purchase_price_including_tax',

        // بيانات قديمة نحتفظ بها بدون استخدامها حاليًا.
        'sales_date',
        'sales_item_id',
        'sales_invoice_number',
        'sales_invoice_total',
        'supplier_id',
        'supplier_name',
        'supplier_address',
        'supplier_phone',
        'customer_id',
        'customer_name',
        'supplier_invoice_number',
        'invoice_total',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'purchase_date' => 'date',
        'purchase_quantity' => 'decimal:3',
        'purchase_price' => 'decimal:2',
        'purchase_tax_enabled' => 'boolean',
        'purchase_tax' => 'decimal:2',
        'purchase_price_including_tax' => 'decimal:2',
        'sales_date' => 'date',
        'sales_invoice_total' => 'decimal:2',
        'invoice_total' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OctramEntryItem::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(
            Item::class,
            'purchase_item_id'
        );
    }

    public function salesItem(): BelongsTo
    {
        return $this->belongsTo(
            Item::class,
            'sales_item_id'
        );
    }
}
