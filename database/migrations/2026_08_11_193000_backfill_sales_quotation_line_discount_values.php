<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasColumn('sales_quotation_items', 'discount_type')
            || ! Schema::hasColumn('sales_quotation_items', 'discount_value')
        ) {
            return;
        }

        DB::table('sales_quotation_items')
            ->where('discount_amount', '>', 0)
            ->where(function ($query): void {
                $query->whereNull('discount_value')
                    ->orWhere('discount_value', 0);
            })
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $quantity = (float) ($row->quantity ?? 0);
                    $discountAmount = (float) ($row->discount_amount ?? 0);

                    $perUnitDiscount = $quantity > 0
                        ? round($discountAmount / $quantity, 4)
                        : 0;

                    DB::table('sales_quotation_items')
                        ->where('id', $row->id)
                        ->update([
                            'discount_type' => 'value',
                            'discount_value' => $perUnitDiscount,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // لا نعيد تصفير القيم حتى لا نفقد بيانات الخصومات المحفوظة.
    }
};
