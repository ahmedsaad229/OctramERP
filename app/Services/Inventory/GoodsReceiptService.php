<?php

namespace App\Services\Inventory;

use App\Models\GoodsReceiptVoucher;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;

class GoodsReceiptService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function post(GoodsReceiptVoucher $voucher): void
    {
        $voucher->load('items');

        $this->inventoryService->replaceVoucherTransactions(
            $voucher->code,
            $voucher->warehouse_id,
            $voucher->voucher_date,
            $voucher->notes,
            StockTransaction::TYPE_PURCHASE,
            $voucher->items,
        );

        if (! $voucher->posted) {
            $voucher->posted = true;
            $voucher->save();
        }
    }

    public function delete(GoodsReceiptVoucher $voucher): bool
    {
        return DB::transaction(function () use ($voucher): bool {
            $voucher = GoodsReceiptVoucher::query()->lockForUpdate()->findOrFail($voucher->getKey());
            $this->inventoryService->deleteDocumentTransactions($voucher->code);
            $voucher->items()->delete();

            return (bool) $voucher->delete();
        });
    }
}
