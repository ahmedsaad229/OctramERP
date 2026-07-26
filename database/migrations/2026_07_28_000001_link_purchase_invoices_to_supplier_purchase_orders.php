<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table): void {
            $table->foreignId('supplier_purchase_order_id')
                ->nullable()
                ->after('supplier_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table): void {
            $table->foreignId('supplier_purchase_order_item_id')
                ->nullable()
                ->after('purchase_invoice_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('supplier_purchase_order_item_id');
        });

        Schema::table('purchase_invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('supplier_purchase_order_id');
        });
    }
};
