<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sales_quotations', 'global_discount_amount')) {
            Schema::table('sales_quotations', function (Blueprint $table): void {
                $table->dropColumn('global_discount_amount');
            });
        }

        if (! Schema::hasColumn('sales_quotation_items', 'discount_type')) {
            Schema::table('sales_quotation_items', function (Blueprint $table): void {
                $table->string('discount_type', 20)
                    ->default('value')
                    ->after('unit_price');
            });
        }

        if (! Schema::hasColumn('sales_quotation_items', 'discount_value')) {
            Schema::table('sales_quotation_items', function (Blueprint $table): void {
                $table->decimal('discount_value', 14, 4)
                    ->default(0)
                    ->after('discount_type');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sales_quotations', 'global_discount_amount')) {
            Schema::table('sales_quotations', function (Blueprint $table): void {
                $table->decimal('global_discount_amount', 14, 2)
                    ->default(0)
                    ->after('discount_amount');
            });
        }

        if (Schema::hasColumn('sales_quotation_items', 'discount_value')) {
            Schema::table('sales_quotation_items', function (Blueprint $table): void {
                $table->dropColumn('discount_value');
            });
        }

        if (Schema::hasColumn('sales_quotation_items', 'discount_type')) {
            Schema::table('sales_quotation_items', function (Blueprint $table): void {
                $table->dropColumn('discount_type');
            });
        }
    }
};
