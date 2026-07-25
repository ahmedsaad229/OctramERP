<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();

            $table->unique(['sales_invoice_id', 'item_id']);
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
    }
};
