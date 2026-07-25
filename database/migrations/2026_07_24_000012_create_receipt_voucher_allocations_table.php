<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_voucher_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_voucher_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('electronic_invoice_number');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(
                ['receipt_voucher_id', 'sales_invoice_id'],
                'receipt_allocations_voucher_invoice_unique',
            );
            $table->index('sales_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_voucher_allocations');
    }
};
