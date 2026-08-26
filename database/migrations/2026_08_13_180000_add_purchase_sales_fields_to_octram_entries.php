<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('octram_entries', function (Blueprint $table): void {
            $table->date('purchase_date')->nullable();
            $table->foreignId('purchase_item_id')->nullable()->constrained('items')->nullOnDelete();

            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('purchase_tax', 15, 2)->nullable();
            $table->decimal('purchase_price_including_tax', 15, 2)->nullable();

            $table->date('sales_date')->nullable();
            $table->foreignId('sales_item_id')->nullable()->constrained('items')->nullOnDelete();

            $table->string('sales_invoice_number')->nullable();
            $table->decimal('sales_invoice_total', 15, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('octram_entries', function (Blueprint $table): void {
            $table->dropForeign(['purchase_item_id']);
            $table->dropForeign(['sales_item_id']);

            $table->dropColumn([
                'purchase_date',
                'purchase_item_id',
                'purchase_price',
                'purchase_tax',
                'purchase_price_including_tax',
                'sales_date',
                'sales_item_id',
                'sales_invoice_number',
                'sales_invoice_total',
            ]);
        });
    }
};
