<?php

namespace App\Services;

use App\Enums\PaymentType;
use App\Enums\TaxType;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\SupplierPurchaseOrder;
use App\Services\Documents\DocumentDeletionGuard;
use App\Services\Inventory\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierPurchaseOrderService
{
    /**
     * @return array<int, string>
     */
    public function purchaseRequestOptions(?int $currentPurchaseRequestId = null): array
    {
        return PurchaseRequest::query()
            ->with(['warehouse:id,name', 'requestedBy:id,name'])
            ->where(function (Builder $query) use ($currentPurchaseRequestId): void {
                $query
                    ->whereHas('items', fn (Builder $query): Builder => $query
                        ->whereRaw('purchase_request_items.requested_quantity > (
                            SELECT COALESCE(SUM(supplier_purchase_order_items.ordered_quantity), 0)
                            FROM supplier_purchase_order_items
                            WHERE supplier_purchase_order_items.purchase_request_item_id = purchase_request_items.id
                        )'))
                    ->when(
                        $currentPurchaseRequestId,
                        fn (Builder $query): Builder => $query->orWhere(
                            'purchase_requests.id',
                            $currentPurchaseRequestId,
                        ),
                    );
            })
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(fn (PurchaseRequest $request): array => [
                $request->getKey() => $this->purchaseRequestOptionLabel($request),
            ])
            ->all();
    }

    public function purchaseRequestOptionLabel(PurchaseRequest $request): string
    {
        $parts = [
            $request->code,
            'طلب بتاريخ '.$request->request_date->format('d/m/Y'),
        ];

        if (filled($request->purpose)) {
            $parts[] = $request->purpose;
        }

        if ($request->warehouse) {
            $parts[] = $request->warehouse->name;
        }

        return implode(' — ', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    public function requestSelectionPayload(
        int $purchaseRequestId,
        ?int $excludingPurchaseOrderId = null,
    ): array {
        $request = PurchaseRequest::query()
            ->with(['warehouse:id,name', 'requestedBy:id,name'])
            ->findOrFail($purchaseRequestId);

        return [
            'purchase_request_id' => $request->getKey(),
            'warehouse_id' => $request->warehouse_id,
            'request_required_date' => $request->required_date?->format('Y-m-d'),
            'request_purpose' => $request->purpose,
            'request_department' => $request->department,
            'request_requester' => $request->requestedBy?->name,
            'items' => $this->importRemainingItems($request->getKey(), $excludingPurchaseOrderId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function importRemainingItems(int $purchaseRequestId, ?int $excludingPurchaseOrderId = null): array
    {
        $request = PurchaseRequest::query()->findOrFail($purchaseRequestId);
        $inventoryService = app(InventoryService::class);

        return PurchaseRequestItem::query()
            ->where('purchase_request_id', $purchaseRequestId)
            ->with(['item:id,code,name,unit_id', 'unit:id,name'])
            ->orderBy('sort_order')
            ->get()
            ->map(function (PurchaseRequestItem $requestItem) use (
                $excludingPurchaseOrderId,
                $inventoryService,
                $request,
            ): ?array {
                $ordered = $requestItem->orderedQuantity($excludingPurchaseOrderId);
                $remaining = $requestItem->remainingToOrderQuantity($excludingPurchaseOrderId);

                if ($remaining <= 0) {
                    return null;
                }

                return [
                    'purchase_request_item_id' => $requestItem->getKey(),
                    'item_id' => $requestItem->item_id,
                    'item_code' => $requestItem->item->code,
                    'item_name' => $requestItem->item->name,
                    'unit_id' => $requestItem->unit_id,
                    'unit_name' => $requestItem->unit->name,
                    'requested_quantity' => (float) $requestItem->requested_quantity,
                    'previously_ordered_quantity' => $ordered,
                    'remaining_quantity' => $remaining,
                    'warehouse_balance' => $inventoryService->warehouseBalance(
                        $request->warehouse_id,
                        $requestItem->item_id,
                    ),
                    'total_balance' => $inventoryService->totalBalance($requestItem->item_id),
                    'ordered_quantity' => $remaining,
                    'unit_price' => 0,
                    'line_total' => 0,
                    'notes' => null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function create(array $data): SupplierPurchaseOrder
    {
        $data['tax_type'] ??= app(CompanyTaxSetting::class)->resolve()->value;

        return DB::transaction(function () use ($data): SupplierPurchaseOrder {
            $this->normalizeHeader($data);
            $requestId = (int) ($data['purchase_request_id'] ?? 0);
            $items = $data['items'] ?? $this->importRemainingItems($requestId);
            $items = $this->validatedItems($items, $requestId);
            unset($data['items'], $data['code']);

            $data['created_by'] ??= auth()->id();
            $totals = $this->totals($items, $data);
            $order = SupplierPurchaseOrder::create([...$data, ...$totals]);
            $this->replaceItems($order, $items);

            return $order->load('items');
        });
    }

    public function update(SupplierPurchaseOrder $order, array $data): SupplierPurchaseOrder
    {
        return DB::transaction(function () use ($order, $data): SupplierPurchaseOrder {
            $order = SupplierPurchaseOrder::query()->lockForUpdate()->findOrFail($order->getKey());
            $data['tax_type'] ??= $order->tax_type?->value ?? TaxType::Vat14->value;
            $this->normalizeHeader($data);
            $requestId = (int) ($data['purchase_request_id'] ?? 0);
            $items = $this->validatedItems($data['items'] ?? [], $requestId, $order->getKey());
            unset($data['items'], $data['code'], $data['created_by']);

            $totals = $this->totals($items, $data);
            $order->update([...$data, ...$totals]);
            $order->items()->delete();
            $this->replaceItems($order, $items);

            return $order->load('items');
        });
    }

    public function delete(SupplierPurchaseOrder $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $order = SupplierPurchaseOrder::query()->lockForUpdate()->findOrFail($order->getKey());
            app(DocumentDeletionGuard::class)->assertCanDelete($order);

            $order->items()->delete();

            return (bool) $order->delete();
        });
    }

    private function normalizeHeader(array &$data): void
    {
        if (! (int) ($data['supplier_id'] ?? 0)) {
            throw ValidationException::withMessages(['supplier_id' => 'المورد مطلوب.']);
        }

        if (! PurchaseRequest::query()->whereKey($data['purchase_request_id'] ?? 0)->exists()) {
            throw ValidationException::withMessages([
                'purchase_request_id' => 'يجب اختيار طلب شراء لإنشاء أمر التوريد.',
            ]);
        }

        unset(
            $data['request_required_date'],
            $data['request_purpose'],
            $data['request_department'],
            $data['request_requester'],
        );

        if (($data['payment_type'] ?? null) === PaymentType::Cash->value) {
            $data['due_date'] = null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validatedItems(array $items, int $requestId, ?int $excludingOrderId = null): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'يجب إضافة صنف واحد على الأقل.']);
        }

        $seen = [];

        foreach ($items as $index => &$item) {
            $requestItem = PurchaseRequestItem::query()
                ->whereKey($item['purchase_request_item_id'] ?? 0)
                ->where('purchase_request_id', $requestId)
                ->first();

            if (! $requestItem || (int) $requestItem->item_id !== (int) ($item['item_id'] ?? 0)) {
                throw ValidationException::withMessages(["items.{$index}.item_id" => 'الصنف لا ينتمي إلى طلب الشراء المحدد.']);
            }

            $quantity = (float) ($item['ordered_quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $remaining = $requestItem->remainingToOrderQuantity($excludingOrderId);

            if ($quantity <= 0) {
                throw ValidationException::withMessages(["items.{$index}.ordered_quantity" => 'يجب أن تكون كمية أمر التوريد أكبر من صفر.']);
            }

            if ($quantity > $remaining + 0.00001) {
                throw ValidationException::withMessages([
                    "items.{$index}.ordered_quantity" => 'الكمية المطلوبة في أمر التوريد أكبر من الكمية المتبقية في طلب الشراء.',
                ]);
            }

            if ($unitPrice < 0) {
                throw ValidationException::withMessages(["items.{$index}.unit_price" => 'لا يمكن أن يكون سعر الوحدة سالبًا.']);
            }

            if (isset($seen[$requestItem->getKey()])) {
                throw ValidationException::withMessages(["items.{$index}.item_id" => 'لا يمكن تكرار الصنف في أمر التوريد.']);
            }

            $seen[$requestItem->getKey()] = true;
            $item['unit_id'] = $requestItem->unit_id;
            $item['line_total'] = round($quantity * $unitPrice, 2);
            $item['sort_order'] = $index;
            unset(
                $item['item_code'],
                $item['item_name'],
                $item['unit_name'],
                $item['requested_quantity'],
                $item['previously_ordered_quantity'],
                $item['remaining_quantity'],
                $item['warehouse_balance'],
                $item['total_balance'],
            );
        }

        return $items;
    }

    /**
     * @return array{subtotal: float, discount_amount: float, tax_amount: float, total: float}
     */
    private function totals(array $items, array $data): array
    {
        $subtotal = round((float) collect($items)->sum('line_total'), 2);
        $discount = max(0, round((float) ($data['discount_amount'] ?? 0), 2));
        $taxType = TaxType::tryFrom((string) ($data['tax_type'] ?? TaxType::None->value));

        if (! $taxType) {
            throw ValidationException::withMessages(['tax_type' => 'نوع الضريبة المحدد غير صالح.']);
        }

        $calculation = app(DocumentTaxCalculator::class)->calculate($subtotal, $discount, $taxType);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_type' => $taxType->value,
            'tax_amount' => $calculation['tax_amount'],
            'total' => $calculation['total'],
        ];
    }

    private function replaceItems(SupplierPurchaseOrder $order, array $items): void
    {
        foreach ($items as $item) {
            $order->items()->create($item);
        }
    }
}
