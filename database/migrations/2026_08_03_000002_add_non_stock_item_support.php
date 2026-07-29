<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->boolean('is_stock_item')->default(true)->index()->after('name_en');
        });

        Schema::table('sales_quotation_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('unit_id')->nullable()->change();
        });

        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->unsignedBigInteger('warehouse_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->unsignedBigInteger('warehouse_id')->nullable(false)->change();
        });

        Schema::table('sales_quotation_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('unit_id')->nullable(false)->change();
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->dropIndex(['is_stock_item']);
            $table->dropColumn('is_stock_item');
        });
    }
};
