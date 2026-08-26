<?php

namespace App\Models\Concerns;

use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait LocksClosedFiscalPeriods
{
    protected static function bootLocksClosedFiscalPeriods(): void
    {
        static::saving(function (Model $model): void {
            static::assertFiscalPeriodOpenForModel($model);
        });

        static::deleting(function (Model $model): void {
            static::assertFiscalPeriodOpenForModel($model);
        });
    }

    protected static function assertFiscalPeriodOpenForModel(Model $model): void
    {
        // جدول السنوات نفسه يجب أن يظل قابلاً للإدارة/إلغاء الإقفال.
        if ($model instanceof FiscalYear) {
            return;
        }

        $date = static::fiscalDocumentDate($model);

        if (! $date) {
            return;
        }

        $closedYear = FiscalYear::query()
            ->where('status', FiscalYear::STATUS_CLOSED)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($closedYear) {
            throw ValidationException::withMessages([
                'fiscal_year' => "الفترة المالية مقفلة ({$closedYear->name}). لا يمكن إضافة أو تعديل أو حذف مستند بتاريخ {$date}.",
            ]);
        }
    }

    protected static function fiscalDocumentDate(Model $model): ?string
    {
        $attributes = $model->getAttributes();

        foreach ([
            'entry_date',
            'invoice_date',
            'voucher_date',
            'order_date',
            'quotation_date',
            'request_date',
            'transaction_date',
            'document_date',
            'date',
        ] as $field) {
            if (! empty($attributes[$field])) {
                try {
                    return \Illuminate\Support\Carbon::parse($attributes[$field])->toDateString();
                } catch (\Throwable) {
                    return null;
                }
            }
        }

        return null;
    }
}
