<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales_invoice_items', 'tax_exempt')) {
            Schema::table('sales_invoice_items', function (Blueprint $table): void {
                $table->boolean('tax_exempt')
                    ->default(false)
                    ->after('discount_amount');
            });
        }

        if (! Schema::hasColumn('customer_purchase_order_items', 'discount_amount')) {
            Schema::table('customer_purchase_order_items', function (Blueprint $table): void {
                $table->decimal('discount_amount', 14, 2)
                    ->default(0)
                    ->after('unit_price');
            });
        }

        if (! Schema::hasColumn('customer_purchase_order_items', 'tax_exempt')) {
            Schema::table('customer_purchase_order_items', function (Blueprint $table): void {
                $table->boolean('tax_exempt')
                    ->default(false)
                    ->after('discount_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_invoice_items', 'tax_exempt')) {
            Schema::table('sales_invoice_items', function (Blueprint $table): void {
                $table->dropColumn('tax_exempt');
            });
        }

        if (Schema::hasColumn('customer_purchase_order_items', 'tax_exempt')) {
            Schema::table('customer_purchase_order_items', function (Blueprint $table): void {
                $table->dropColumn('tax_exempt');
            });
        }

        if (Schema::hasColumn('customer_purchase_order_items', 'discount_amount')) {
            Schema::table('customer_purchase_order_items', function (Blueprint $table): void {
                $table->dropColumn('discount_amount');
            });
        }
    }
};
