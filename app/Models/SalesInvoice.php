<?php

namespace App\Models;

use App\Services\DocumentNumberService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends BaseModel
{
    protected $fillable = [
        'document_number',
        'invoice_date',
        'customer_id',
        'warehouse_id',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesInvoice $invoice): void {
            if (blank($invoice->document_number)) {
                $invoice->document_number = app(DocumentNumberService::class)
                    ->generate(DocumentNumberService::SALES_INVOICE);
            }
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

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }
}
