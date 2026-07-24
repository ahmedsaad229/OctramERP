<?php

namespace App\Services\Inventory;

use App\Models\PurchaseInvoice;
use App\Models\PartyTransaction;
use App\Models\StockTransaction;
use App\Services\PartyTransactionService;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly PartyTransactionService $partyTransactionService,
    ) {}

    public function post(PurchaseInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $invoice->load('items', 'supplier');

            $this->inventoryService->replaceVoucherTransactions(
                $invoice->code,
                $invoice->warehouse_id,
                $invoice->invoice_date,
                $invoice->notes,
                StockTransaction::TYPE_PURCHASE,
                $invoice->items,
            );

            $this->partyTransactionService->replaceDocumentTransaction(
                $invoice->supplier,
                PartyTransaction::TYPE_PURCHASE_INVOICE,
                $invoice,
                $invoice->invoice_date,
                0,
                (float) $invoice->items->sum(
                    fn ($item): float => (float) $item->quantity * (float) $item->unit_cost,
                ),
                $invoice->code,
                $invoice->notes,
            );

            if (! $invoice->posted) {
                $invoice->posted = true;
                $invoice->save();
            }
        });
    }
}
