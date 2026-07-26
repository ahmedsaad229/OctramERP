<?php

namespace App\Services;

use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Services\Documents\DocumentDeletionGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseRequestService
{
    public function create(array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($data): PurchaseRequest {
            $items = $this->validatedItems($data['items'] ?? []);
            unset($data['items'], $data['code']);

            $data['created_by'] ??= auth()->id();
            $data['requested_by'] = auth()->id();
            $request = PurchaseRequest::create($data);
            $this->replaceItems($request, $items);

            return $request->load('items');
        });
    }

    public function update(PurchaseRequest $request, array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($request, $data): PurchaseRequest {
            $request = PurchaseRequest::query()->lockForUpdate()->findOrFail($request->getKey());
            $items = $this->validatedItems($data['items'] ?? [], $request);
            unset($data['items'], $data['code'], $data['created_by'], $data['requested_by']);

            $request->update($data);
            $this->syncItems($request, $items);

            return $request->load('items');
        });
    }

    public function delete(PurchaseRequest $request): bool
    {
        return DB::transaction(function () use ($request): bool {
            $request = PurchaseRequest::query()->lockForUpdate()->findOrFail($request->getKey());
            app(DocumentDeletionGuard::class)->assertCanDelete($request);

            return (bool) $request->delete();
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validatedItems(array $items, ?PurchaseRequest $request = null): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'يجب إضافة صنف واحد على الأقل.']);
        }

        $seen = [];

        foreach ($items as $index => &$item) {
            $itemId = (int) ($item['item_id'] ?? 0);

            if ($itemId <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.item_id" => 'يجب اختيار الصنف.',
                ]);
            }

            $inventoryItem = Item::query()->find($itemId);

            if (! $inventoryItem) {
                throw ValidationException::withMessages(["items.{$index}.item_id" => 'الصنف المحدد غير صالح.']);
            }

            if (! $inventoryItem->unit_id) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_id" => 'الصنف المختار لا توجد له وحدة افتراضية. يرجى تسجيل الوحدة في بطاقة الصنف أولًا.',
                ]);
            }

            $item['unit_id'] = $inventoryItem->unit_id;

            if (! array_key_exists('requested_quantity', $item) || blank($item['requested_quantity'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.requested_quantity" => 'يجب إدخال الكمية المطلوبة.',
                ]);
            }

            $quantity = (float) $item['requested_quantity'];

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.requested_quantity" => 'يجب أن تكون الكمية المطلوبة أكبر من صفر.',
                ]);
            }

            if (isset($seen[$itemId])) {
                throw ValidationException::withMessages(["items.{$index}.item_id" => 'لا يمكن تكرار الصنف في طلب الشراء.']);
            }

            if ($request) {
                $existingItem = $request->items()->where('item_id', $itemId)->first();

                if ($existingItem && $quantity < $existingItem->orderedQuantity()) {
                    throw ValidationException::withMessages([
                        "items.{$index}.requested_quantity" => 'لا يمكن تقليل الكمية عن الكمية التي صدرت بها أوامر توريد.',
                    ]);
                }
            }

            $seen[$itemId] = true;
            $item['sort_order'] = $index;
        }

        return $items;
    }

    private function replaceItems(PurchaseRequest $request, array $items): void
    {
        foreach ($items as $item) {
            $request->items()->create($item);
        }
    }

    private function syncItems(PurchaseRequest $request, array $items): void
    {
        $existing = $request->items()->get()->keyBy('item_id');
        $keptIds = [];

        foreach ($items as $item) {
            $requestItem = $existing->get((int) $item['item_id']);

            if ($requestItem) {
                $requestItem->update($item);
            } else {
                $requestItem = $request->items()->create($item);
            }

            $keptIds[] = $requestItem->getKey();
        }

        $removedItems = $request->items()->whereNotIn('id', $keptIds)->get();

        if ($removedItems->contains(fn (PurchaseRequestItem $item): bool => $item->purchaseOrderItems()->exists())) {
            throw ValidationException::withMessages([
                'items' => 'لا يمكن حذف صنف صدرت له أوامر توريد من طلب الشراء.',
            ]);
        }

        $request->items()->whereNotIn('id', $keptIds)->delete();
    }
}
