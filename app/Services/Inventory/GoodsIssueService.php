<?php

namespace App\Services\Inventory;

use App\Models\GoodsIssueVoucher;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsIssueService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function post(GoodsIssueVoucher $voucher): void
    {
        $voucher->load('items');

        $this->inventoryService->replaceVoucherTransactions(
            $voucher->code,
            $voucher->warehouse_id,
            $voucher->voucher_date,
            $voucher->notes,
            StockTransaction::TYPE_SALE,
            $voucher->items,
        );

        if (! $voucher->posted) {
            $voucher->posted = true;
            $voucher->save();
        }
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): GoodsIssueVoucher
    {
        return DB::transaction(function () use ($data): GoodsIssueVoucher {
            $items = $this->normalizeItems($data['items'] ?? []);
            unset($data['items'], $data['code'], $data['posted']);

            $voucher = GoodsIssueVoucher::create($data);
            $voucher->items()->createMany($items);
            $this->post($voucher);

            return $voucher->fresh(['items', 'warehouse']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(GoodsIssueVoucher $voucher, array $data): GoodsIssueVoucher
    {
        return DB::transaction(function () use ($voucher, $data): GoodsIssueVoucher {
            $voucher = GoodsIssueVoucher::query()->lockForUpdate()->findOrFail($voucher->getKey());
            $items = $this->normalizeItems($data['items'] ?? []);
            unset($data['items'], $data['code'], $data['posted']);

            $voucher->update($data);
            $voucher->items()->delete();
            $voucher->items()->createMany($items);
            $this->post($voucher);

            return $voucher->fresh(['items', 'warehouse']);
        });
    }

    public function delete(GoodsIssueVoucher $voucher): bool
    {
        return DB::transaction(function () use ($voucher): bool {
            $voucher = GoodsIssueVoucher::query()->lockForUpdate()->findOrFail($voucher->getKey());
            $this->inventoryService->deleteDocumentTransactions($voucher->code);
            $voucher->items()->delete();

            return (bool) $voucher->delete();
        });
    }

    /** @return array<int, array<string, mixed>> */
    private function normalizeItems(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'يجب إضافة صنف واحد على الأقل.',
            ]);
        }

        return collect($items)->values()->map(function (array $item, int $index): array {
            $itemId = (int) ($item['item_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);

            if ($itemId <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.item_id" => 'يجب اختيار الصنف.',
                ]);
            }

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'يجب أن تكون الكمية أكبر من صفر.',
                ]);
            }

            if ($unitCost < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_cost" => 'لا يمكن أن تكون تكلفة الوحدة سالبة.',
                ]);
            }

            return [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'notes' => $item['notes'] ?? null,
            ];
        })->all();
    }
}
