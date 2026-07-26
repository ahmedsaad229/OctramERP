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

    public function supplierPaymentAllocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentVoucherAllocation::class);
    }

    public function totalAmount(): float
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return (float) $items->sum(
            fn (PurchaseInvoiceItem $item): float => (float) $item->quantity * (float) $item->unit_cost,
        );
    }

    public function paidAmount(?int $excludingSupplierPaymentVoucherId = null): float
    {
        if ($this->relationLoaded('supplierPaymentAllocations')) {
            return (float) $this->supplierPaymentAllocations
                ->when(
                    $excludingSupplierPaymentVoucherId,
                    fn ($allocations) => $allocations->where(
                        'supplier_payment_voucher_id',
                        '!=',
                        $excludingSupplierPaymentVoucherId,
                    ),
                )
                ->sum('amount');
        }

        return (float) $this->supplierPaymentAllocations()
            ->when(
                $excludingSupplierPaymentVoucherId,
                fn ($query) => $query->where(
                    'supplier_payment_voucher_id',
                    '!=',
                    $excludingSupplierPaymentVoucherId,
                ),
            )
            ->sum('amount');
    }

    public function remainingAmount(?int $excludingSupplierPaymentVoucherId = null): float
    {
        return max(0, $this->totalAmount() - $this->paidAmount($excludingSupplierPaymentVoucherId));
    }

    public function previouslyPaidBeforeSupplierPayment(SupplierPaymentVoucher $voucher): float
    {
        if ($this->relationLoaded('supplierPaymentAllocations')) {
            return (float) $this->supplierPaymentAllocations
                ->filter(function (SupplierPaymentVoucherAllocation $allocation) use ($voucher): bool {
                    $allocatedVoucher = $allocation->supplierPaymentVoucher;

                    if (! $allocatedVoucher || $allocatedVoucher->is($voucher)) {
                        return false;
                    }

                    return $allocatedVoucher->voucher_date->lt($voucher->voucher_date)
                        || (
                            $allocatedVoucher->voucher_date->equalTo($voucher->voucher_date)
                            && $allocatedVoucher->getKey() < $voucher->getKey()
                        );
                })
                ->sum('amount');
        }

        return (float) $this->supplierPaymentAllocations()
            ->whereHas('supplierPaymentVoucher', fn ($query) => $query
                ->where('voucher_date', '<', $voucher->voucher_date)
                ->orWhere(function ($query) use ($voucher): void {
                    $query
                        ->whereDate('voucher_date', $voucher->voucher_date)
                        ->where('id', '<', $voucher->getKey());
                }))
            ->sum('amount');
    }

    public function remainingBeforeSupplierPayment(SupplierPaymentVoucher $voucher): float
    {
        return max(0, $this->totalAmount() - $this->previouslyPaidBeforeSupplierPayment($voucher));
    }

    public function paymentStatus(): string
    {
        $paid = $this->paidAmount();

        if ($paid <= 0) {
            return 'unpaid';
        }

        return $this->remainingAmount() > 0 ? 'partially_paid' : 'fully_paid';
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->paymentStatus()) {
            'partially_paid' => 'مدفوعة جزئيًا',
            'fully_paid' => 'مدفوعة بالكامل',
            default => 'غير مدفوعة',
        };
    }
}
