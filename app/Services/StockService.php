<?php

namespace App\Services;

use App\Models\StockBalance;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    /**
     * إنشاء حركة مخزنية وتحديث الرصيد.
     */
    public function addTransaction(array $data): StockTransaction
    {
        return DB::transaction(function () use ($data) {

            $transaction = StockTransaction::create($data);

            $balance = StockBalance::firstOrCreate(
                [
                    'warehouse_id' => $data['warehouse_id'],
                    'item_id'      => $data['item_id'],
                ],
                [
                    'quantity'     => 0,
                    'average_cost' => 0,
                ]
            );

            switch ($data['transaction_type']) {

                case StockTransaction::TYPE_OPENING:
                case StockTransaction::TYPE_PURCHASE:
                case StockTransaction::TYPE_TRANSFER_IN:

                    $this->increaseStock(
                        $balance,
                        (float) $data['quantity'],
                        (float) $data['unit_cost']
                    );

                    break;

                case StockTransaction::TYPE_SALE:
                case StockTransaction::TYPE_TRANSFER_OUT:

                    $this->decreaseStock(
                        $balance,
                        (float) $data['quantity']
                    );

                    break;

                case StockTransaction::TYPE_ADJUSTMENT:

                    $balance->quantity = (float) $data['quantity'];

                    if ((float) $data['unit_cost'] > 0) {
                        $balance->average_cost = (float) $data['unit_cost'];
                    }

                    $balance->save();

                    break;
            }

            return $transaction;
        });
    }

    /**
     * زيادة الرصيد مع حساب متوسط التكلفة.
     */
    protected function increaseStock(
        StockBalance $balance,
        float $qty,
        float $cost
    ): void {

        $oldQty  = (float) $balance->quantity;
        $oldCost = (float) $balance->average_cost;

        $newQty = $oldQty + $qty;

        if ($newQty > 0) {
            $balance->average_cost =
                (($oldQty * $oldCost) + ($qty * $cost)) / $newQty;
        }

        $balance->quantity = $newQty;

        $balance->save();
    }

    /**
     * خصم من الرصيد.
     */
    protected function decreaseStock(
        StockBalance $balance,
        float $qty
    ): void {

        if ((float) $balance->quantity < $qty) {
            throw new RuntimeException('الكمية غير متوفرة بالمخزن.');
        }

        $balance->quantity = (float) $balance->quantity - $qty;

        $balance->save();
    }
}