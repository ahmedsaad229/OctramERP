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
        Schema::create('items', function (Blueprint $table) {

            $table->id();

            $table->string('code')->unique();

            $table->string('sku')->nullable()->unique();

            $table->string('barcode')->nullable()->unique();

            $table->string('name');

            $table->string('name_en')->nullable();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('purchase_price', 15, 2)->default(0);

            $table->decimal('sale_price', 15, 2)->default(0);

            $table->decimal('minimum_stock', 15, 2)->default(0);

            $table->boolean('allow_negative_stock')->default(false);

            $table->boolean('active')->default(true);

            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};