<?php

namespace App\Services\Inventory;

use App\Models\OpeningStockVoucher;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;

class OpeningStockService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function post(OpeningStockVoucher $voucher): void
    {
        $voucher->load('items');

        $this->inventoryService->replaceVoucherTransactions(
            $voucher->code,
            $voucher->warehouse_id,
            $voucher->voucher_date,
            $voucher->notes,
            StockTransaction::TYPE_OPENING,
            $voucher->items,
        );

        if (! $voucher->posted) {
            $voucher->posted = true;
            $voucher->save();
        }
    }

    public function delete(OpeningStockVoucher $voucher): bool
    {
        return DB::transaction(function () use ($voucher): bool {
            $voucher = OpeningStockVoucher::query()->lockForUpdate()->findOrFail($voucher->getKey());
            $this->inventoryService->deleteDocumentTransactions($voucher->code);
            $voucher->items()->delete();

            return (bool) $voucher->delete();
        });
    }
}
