<?php

namespace App\Services\Inventory;

use App\Models\OpeningStockVoucher;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;

class OpeningStockService
{
    public function post(OpeningStockVoucher $voucher): void
    {
        DB::transaction(function () use ($voucher) {

            // تحميل الأصناف
            $voucher->load('items');

            // حذف الحركات القديمة الخاصة بالمستند
            StockTransaction::where('transaction_type', StockTransaction::TYPE_OPENING)
                ->where('reference_no', $voucher->code)
                ->delete();

            foreach ($voucher->items as $item) {

                // إنشاء حركة مخزن
                StockTransaction::create([
                    'warehouse_id'     => $voucher->warehouse_id,
                    'item_id'          => $item->item_id,
                    'transaction_type' => StockTransaction::TYPE_OPENING,
                    'quantity'         => $item->quantity,
                    'unit_cost'        => $item->unit_cost,
                    'transaction_date' => $voucher->voucher_date,
                    'reference_no'     => $voucher->code,
                    'notes'            => $voucher->notes,
                ]);

                // تحديث رصيد المخزن
                $balance = StockBalance::firstOrNew([
                    'warehouse_id' => $voucher->warehouse_id,
                    'item_id'      => $item->item_id,
                ]);

                $balance->quantity = $item->quantity;
                $balance->average_cost = $item->unit_cost;

                $balance->save();
            }

            // اعتبار المستند مرحلاً
            if (!$voucher->posted) {
                $voucher->posted = true;
                $voucher->save();
            }
        });
    }
}