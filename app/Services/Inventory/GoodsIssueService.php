<?php

namespace App\Services\Inventory;

use App\Models\GoodsIssueVoucher;
use App\Models\StockTransaction;

class GoodsIssueService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {
    }

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
}
