<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('octram_entries', function (Blueprint $table): void {
            $table->id();
            $table->date('entry_date');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('supplier_name')->nullable();
            $table->string('supplier_address')->nullable();
            $table->string('supplier_phone')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('supplier_invoice_number')->nullable();
            $table->decimal('invoice_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('octram_entry_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('octram_entry_id')
                ->constrained('octram_entries')
                ->cascadeOnDelete();

            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name')->nullable();

            $table->decimal('price_before_tax', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('price_including_tax', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('octram_entry_items');
        Schema::dropIfExists('octram_entries');
    }
};