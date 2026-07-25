<?php

namespace App\Models\Concerns;

use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

trait HasInformationalPaymentTerms
{
    public const DUE_STATUS_CASH = 'cash';

    public const DUE_STATUS_UPCOMING = 'upcoming';

    public const DUE_STATUS_TODAY = 'due_today';

    public const DUE_STATUS_OVERDUE = 'overdue';

    protected static function bootHasInformationalPaymentTerms(): void
    {
        static::saving(function ($invoice): void {
            $paymentType = $invoice->payment_type instanceof PaymentType
                ? $invoice->payment_type
                : PaymentType::tryFrom((string) $invoice->payment_type);

            if (! $paymentType) {
                throw ValidationException::withMessages([
                    'payment_type' => 'نوع التعامل مطلوب ويجب أن يكون كاش أو آجل.',
                ]);
            }

            if ($paymentType === PaymentType::Cash) {
                $invoice->due_date = null;

                return;
            }

            if (blank($invoice->due_date)) {
                throw ValidationException::withMessages([
                    'due_date' => 'تاريخ الاستحقاق مطلوب عند اختيار التعامل الآجل.',
                ]);
            }

            if ($invoice->due_date->lt($invoice->invoice_date)) {
                throw ValidationException::withMessages([
                    'due_date' => 'تاريخ الاستحقاق يجب أن يكون في تاريخ الفاتورة أو بعده.',
                ]);
            }
        });
    }

    public function setPaymentTypeAttribute(PaymentType|string|null $value): void
    {
        $paymentType = $value instanceof PaymentType
            ? $value
            : PaymentType::tryFrom((string) $value);

        if (! $paymentType) {
            throw ValidationException::withMessages([
                'payment_type' => 'نوع التعامل مطلوب ويجب أن يكون كاش أو آجل.',
            ]);
        }

        $this->attributes['payment_type'] = $paymentType->value;
    }

    public function dueStatus(): string
    {
        if ($this->payment_type === PaymentType::Cash || ! $this->due_date) {
            return self::DUE_STATUS_CASH;
        }

        if ($this->due_date->isToday()) {
            return self::DUE_STATUS_TODAY;
        }

        return $this->due_date->isPast()
            ? self::DUE_STATUS_OVERDUE
            : self::DUE_STATUS_UPCOMING;
    }

    public function dueStatusLabel(): string
    {
        return match ($this->dueStatus()) {
            self::DUE_STATUS_UPCOMING => 'مستحق لاحقاً',
            self::DUE_STATUS_TODAY => 'مستحق اليوم',
            self::DUE_STATUS_OVERDUE => 'متأخر',
            default => 'كاش',
        };
    }

    public function scopeDueStatus(Builder $query, string $status): Builder
    {
        $today = now()->toDateString();

        return match ($status) {
            self::DUE_STATUS_UPCOMING => $query
                ->where('payment_type', PaymentType::Credit->value)
                ->whereDate('due_date', '>', $today),
            self::DUE_STATUS_TODAY => $query
                ->where('payment_type', PaymentType::Credit->value)
                ->whereDate('due_date', $today),
            self::DUE_STATUS_OVERDUE => $query
                ->where('payment_type', PaymentType::Credit->value)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today),
            default => $query->where('payment_type', PaymentType::Cash->value),
        };
    }
}
