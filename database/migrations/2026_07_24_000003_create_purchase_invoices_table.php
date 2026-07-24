<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->foreignId('warehouse_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('posted')->default(false);
            $table->timestamps();
            $table->unique(['supplier_id', 'invoice_number']);
        });
    }

    public function down(): void { Schema::dropIfExists('purchase_invoices'); }
};
