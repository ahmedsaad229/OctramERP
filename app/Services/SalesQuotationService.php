<?php

namespace App\Services;

use App\Enums\TaxType;
use App\Models\Item;
use App\Models\SalesQuotation;
use App\Models\Unit;
use App\Services\Documents\DocumentDeletionGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SalesQuotationService
{
    public function create(array $data): SalesQuotation
    {
        $data['tax_type'] ??= app(CompanyTaxSetting::class)->resolve()->value;

        return DB::transaction(function () use ($data): SalesQuotation {
            [$data, $items] = $this->validatedPayload($data);
            $quotation = SalesQuotation::create($data);
            $quotation->items()->createMany($items);

            return $quotation->fresh(['items.item.unit', 'customer', 'warehouse']);
        });
    }

    public function update(SalesQuotation $quotation, array $data): SalesQuotation
    {
        return DB::transaction(function () use ($quotation, $data): SalesQuotation {
            $quotation = SalesQuotation::query()->lockForUpdate()->findOrFail($quotation->getKey());
            $data['tax_type'] ??= $quotation->tax_type->value;
            if ($quotation->salesInvoices()->exists()) {
                throw ValidationException::withMessages([
                    'items' => 'لا يمكن تعديل عرض سعر مرتبط بفواتير بيع.',
                ]);
            }
            [$data, $items] = $this->validatedPayload($data);
            unset($data['quotation_number'], $data['created_by']);
            $quotation->update($data);
            $quotation->items()->delete();
            $quotation->items()->createMany($items);

            return $quotation->fresh(['items.item.unit', 'customer', 'warehouse']);
        });
    }

    public function delete(SalesQuotation $quotation): bool
    {
        return DB::transaction(function () use ($quotation): bool {
            $quotation = SalesQuotation::query()->lockForUpdate()->findOrFail($quotation->getKey());
            app(DocumentDeletionGuard::class)->assertCanDelete($quotation);
            $quotation->items()->delete();

            return (bool) $quotation->delete();
        });
    }

    /** @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>} */
    private function validatedPayload(array $data): array
    {
        Validator::make($data, [
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'tax_type' => ['required'],
            'exclude_totals' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
        ], [
            'valid_until.after_or_equal' => 'تاريخ انتهاء الصلاحية يجب ألا يسبق تاريخ عرض السعر.',
        ])->validate();

        $taxType = TaxType::tryFrom((string) $data['tax_type']);
        if (! $taxType) {
            throw ValidationException::withMessages(['tax_type' => 'نوع الضريبة المحدد غير صالح.']);
        }

        $items = collect($data['items'])->values()->map(function (array $row, int $index) use ($taxType): array {
            $item = Item::query()->find($row['item_id'] ?? null);
            $unitId = filled($row['unit_id'] ?? null) ? (int) $row['unit_id'] : null;
            $quantity = (float) ($row['quantity'] ?? 0);
            $unitPrice = (float) ($row['unit_price'] ?? 0);
            $discountType = (string) ($row['discount_type'] ?? 'value');
            $discountValue = max(0, round((float) ($row['discount_value'] ?? 0), 2));
            $base = round($quantity * $unitPrice, 2);

            if (! in_array($discountType, ['value', 'percent'], true)) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount_type" => 'نوع الخصم المحدد غير صالح.',
                ]);
            }

            if ($discountType === 'percent' && $discountValue > 100) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount_value" => 'نسبة الخصم لا يمكن أن تتجاوز 100%.',
                ]);
            }

            if ($discountType === 'value' && $discountValue > $unitPrice) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount_value" => 'قيمة الخصم للوحدة لا يمكن أن تتجاوز سعر الوحدة.',
                ]);
            }

            $discount = $discountType === 'percent'
                ? round($base * $discountValue / 100, 2)
                : round($discountValue * $quantity, 2);

            if (! $item || ! $item->active) {
                throw ValidationException::withMessages(["items.{$index}.item_id" => 'البند المحدد غير موجود أو غير نشط.']);
            }
            if ($item->isStockItem() && (! $item->unit_id || $unitId !== (int) $item->unit_id)) {
                throw ValidationException::withMessages(["items.{$index}.unit_id" => 'يجب تحديد الوحدة الافتراضية الصحيحة للبند الذي يؤثر على المخزون.']);
            }
            if ($item->isNonStockItem() && $unitId && ! Unit::query()->whereKey($unitId)->exists()) {
                throw ValidationException::withMessages(["items.{$index}.unit_id" => 'الوحدة المحددة للبند غير موجودة.']);
            }
            if ($quantity <= 0 || $unitPrice < 0 || $discount < 0 || $discount > $base) {
                throw ValidationException::withMessages(["items.{$index}" => 'بيانات الكمية أو السعر أو الخصم غير صحيحة.']);
            }

            $taxExempt = (bool) ($row['tax_exempt'] ?? false);

            $tax = $taxExempt
                ? 0.0
                : (float) app(DocumentTaxCalculator::class)
                    ->calculate($base, $discount, $taxType)['tax_amount'];

            return [
                'item_id' => $item->getKey(),
                'unit_id' => $item->isNonStockItem() ? $unitId : (int) $item->unit_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discount,
                'tax_exempt' => $taxExempt,
                'tax_amount' => round($tax, 2),
                'line_total' => round($base - $discount + $tax, 2),
                'notes' => $row['notes'] ?? null,
            ];
        })->all();

        $excludeTotals = (bool) ($data['exclude_totals'] ?? false);

        $data['tax_type'] = $taxType->value;
        $data['exclude_totals'] = $excludeTotals;

        $data['subtotal'] = $excludeTotals
            ? 0
            : round(
                (float) collect($items)->sum(
                    fn (array $item): float =>
                        $item['quantity'] * $item['unit_price']
                ),
                2
            );

        $data['discount_amount'] = $excludeTotals
            ? 0
            : round((float) collect($items)->sum('discount_amount'), 2);

        $data['tax_amount'] = $excludeTotals
            ? 0
            : round((float) collect($items)->sum('tax_amount'), 2);

        $data['total_amount'] = $excludeTotals
            ? 0
            : round((float) collect($items)->sum('line_total'), 2);
        $data['created_by'] ??= auth()->id();
        unset($data['items'], $data['quotation_number']);

        return [$data, $items];
    }
}
