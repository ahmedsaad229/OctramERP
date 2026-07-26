<?php

namespace App\Services\Inventory;

use App\Models\SalesInvoice;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class InventoryService
{
    public function warehouseBalance(?int $warehouseId, ?int $itemId): float
    {
        if (! $warehouseId || ! $itemId) {
            return 0.0;
        }

        return (float) (StockBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->value('quantity') ?? 0);
    }

    public function totalBalance(?int $itemId): float
    {
        if (! $itemId) {
            return 0.0;
        }

        return (float) StockBalance::query()
            ->where('item_id', $itemId)
            ->sum('quantity');
    }

    public function availableForSalesInvoice(
        int $warehouseId,
        int $itemId,
        ?int $salesInvoiceId = null,
    ): float {
        if ($warehouseId <= 0 || $itemId <= 0) {
            return 0.0;
        }

        $availableQuantity = (float) (StockBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->value('quantity') ?? 0);

        if (! $salesInvoiceId) {
            return $availableQuantity;
        }

        $invoice = SalesInvoice::query()
            ->whereKey($salesInvoiceId)
            ->where('warehouse_id', $warehouseId)
            ->first(['id', 'document_number']);

        if (! $invoice) {
            return $availableQuantity;
        }

        $previouslyIssuedQuantity = (float) StockTransaction::query()
            ->where('reference_no', $invoice->document_number)
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('transaction_type', StockTransaction::TYPE_SALE)
            ->sum('quantity');

        return $availableQuantity + $previouslyIssuedQuantity;
    }

    public function deleteDocumentTransactions(string $referenceNo): void
    {
        if (blank($referenceNo)) {
            throw new InvalidArgumentException('An inventory document reference is required.');
        }

        $balanceKeys = StockTransaction::query()
            ->where('reference_no', $referenceNo)
            ->get(['warehouse_id', 'item_id'])
            ->map(fn (StockTransaction $transaction): string => $this->balanceKey(
                $transaction->warehouse_id,
                $transaction->item_id,
            ))
            ->unique()
            ->values()
            ->all();

        StockTransaction::query()
            ->where('reference_no', $referenceNo)
            ->delete();

        $this->recalculateBalances($balanceKeys);
    }

    /**
     * Replace the ledger entries belonging to one inventory document and
     * rebuild the balances affected by that document.
     *
     * @param  array<int, array<string, mixed>>  $transactions
     * @return Collection<int, StockTransaction>
     */
    public function replaceDocumentTransactions(string $referenceNo, array $transactions): Collection
    {
        if (blank($referenceNo)) {
            throw new InvalidArgumentException('An inventory document reference is required.');
        }

        return DB::transaction(function () use ($referenceNo, $transactions): Collection {
            $balanceKeys = StockTransaction::query()
                ->where('reference_no', $referenceNo)
                ->get(['warehouse_id', 'item_id'])
                ->map(fn (StockTransaction $transaction): string => $this->balanceKey(
                    $transaction->warehouse_id,
                    $transaction->item_id,
                ))
                ->all();

            StockTransaction::query()
                ->where('reference_no', $referenceNo)
                ->delete();

            $this->ensureOutgoingQuantitiesAreAvailable($transactions);

            $createdTransactions = new Collection(
                array_map(
                    fn (array $transaction): StockTransaction => StockTransaction::create([
                        ...$transaction,
                        'reference_no' => $referenceNo,
                    ]),
                    $transactions,
                ),
            );

            $balanceKeys = [
                ...$balanceKeys,
                ...$createdTransactions
                    ->map(fn (StockTransaction $transaction): string => $this->balanceKey(
                        $transaction->warehouse_id,
                        $transaction->item_id,
                    ))
                    ->all(),
            ];

            $this->recalculateBalances(array_unique($balanceKeys));

            return $createdTransactions;
        });
    }

    /**
     * Build and replace the stock transactions for a simple inventory voucher.
     *
     * @param  iterable<object>  $items
     * @return Collection<int, StockTransaction>
     */
    public function replaceVoucherTransactions(
        string $referenceNo,
        int $warehouseId,
        mixed $transactionDate,
        ?string $notes,
        string $transactionType,
        iterable $items,
    ): Collection {
        $transactions = [];

        foreach ($items as $item) {
            $transactions[] = [
                'warehouse_id' => $warehouseId,
                'item_id' => $item->item_id,
                'transaction_type' => $transactionType,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
                'transaction_date' => $transactionDate,
                'notes' => $notes,
            ];
        }

        return $this->replaceDocumentTransactions($referenceNo, $transactions);
    }

    /**
     * @param  array<int, string>  $balanceKeys
     */
    private function recalculateBalances(array $balanceKeys): void
    {
        foreach ($balanceKeys as $balanceKey) {
            [$warehouseId, $itemId] = array_map('intval', explode(':', $balanceKey));
            [$quantity, $averageCost] = $this->calculateBalance($warehouseId, $itemId);

            StockBalance::query()->updateOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'item_id' => $itemId,
                ],
                [
                    'quantity' => $quantity,
                    'average_cost' => $averageCost,
                ],
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     */
    private function ensureOutgoingQuantitiesAreAvailable(array $transactions): void
    {
        $outgoingQuantities = [];

        foreach ($transactions as $transaction) {
            if ($transaction['transaction_type'] !== StockTransaction::TYPE_SALE) {
                continue;
            }

            $balanceKey = $this->balanceKey($transaction['warehouse_id'], $transaction['item_id']);
            $outgoingQuantities[$balanceKey] = ($outgoingQuantities[$balanceKey] ?? 0.0)
                + (float) $transaction['quantity'];
        }

        foreach ($outgoingQuantities as $balanceKey => $requestedQuantity) {
            [$warehouseId, $itemId] = array_map('intval', explode(':', $balanceKey));

            StockBalance::query()
                ->where('warehouse_id', $warehouseId)
                ->where('item_id', $itemId)
                ->lockForUpdate()
                ->first();

            [$availableQuantity] = $this->calculateBalance($warehouseId, $itemId);

            if ($requestedQuantity > $availableQuantity) {
                throw ValidationException::withMessages([
                    'items' => 'الكمية المطلوبة غير متوفرة في المخزن.',
                ]);
            }
        }
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function calculateBalance(int $warehouseId, int $itemId): array
    {
        $quantity = 0.0;
        $averageCost = 0.0;

        StockTransaction::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->each(function (StockTransaction $transaction) use (&$quantity, &$averageCost): void {
                $transactionQuantity = (float) $transaction->quantity;
                $unitCost = (float) $transaction->unit_cost;

                if (in_array($transaction->transaction_type, [
                    StockTransaction::TYPE_OPENING,
                    StockTransaction::TYPE_PURCHASE,
                    StockTransaction::TYPE_TRANSFER_IN,
                ], true)) {
                    $newQuantity = $quantity + $transactionQuantity;

                    if ($newQuantity > 0) {
                        $averageCost = (($quantity * $averageCost) + ($transactionQuantity * $unitCost)) / $newQuantity;
                    }

                    $quantity = $newQuantity;

                    return;
                }

                if (in_array($transaction->transaction_type, [
                    StockTransaction::TYPE_SALE,
                    StockTransaction::TYPE_TRANSFER_OUT,
                ], true)) {
                    $quantity -= $transactionQuantity;

                    return;
                }

                if ($transaction->transaction_type === StockTransaction::TYPE_ADJUSTMENT) {
                    $quantity = $transactionQuantity;

                    if ($unitCost > 0) {
                        $averageCost = $unitCost;
                    }
                }
            });

        return [$quantity, $averageCost];
    }

    private function balanceKey(int $warehouseId, int $itemId): string
    {
        return "{$warehouseId}:{$itemId}";
    }
}
