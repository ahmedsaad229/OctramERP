<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales_quotations', 'global_discount_amount')) {
            Schema::table('sales_quotations', function (Blueprint $table): void {
                $table->decimal('global_discount_amount', 14, 2)
                    ->default(0)
                    ->after('discount_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_quotations', 'global_discount_amount')) {
            Schema::table('sales_quotations', function (Blueprint $table): void {
                $table->dropColumn('global_discount_amount');
            });
        }
    }
};
