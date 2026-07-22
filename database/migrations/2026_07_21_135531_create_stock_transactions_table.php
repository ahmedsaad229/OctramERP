<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('warehouse_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('transaction_type', [
                'opening',
                'purchase',
                'sale',
                'transfer_in',
                'transfer_out',
                'adjustment',
            ]);

            $table->decimal('quantity', 15, 2);

            $table->decimal('unit_cost', 15, 2)->default(0);

            $table->date('transaction_date');

            $table->string('reference_no')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['warehouse_id', 'item_id']);
            $table->index('transaction_type');
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};