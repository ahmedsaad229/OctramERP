<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payment_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number')->unique();
            $table->date('voucher_date')->index();
            $table->foreignId('supplier_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('treasury_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('payment_method', 30);
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'voucher_date']);
            $table->index(['treasury_id', 'voucher_date']);
            $table->index('payment_method');
        });

        Schema::create('supplier_payment_voucher_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_payment_voucher_id');
            $table->foreign(
                'supplier_payment_voucher_id',
                'spv_allocations_voucher_fk',
            )
                ->references('id')
                ->on('supplier_payment_vouchers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id');
            $table->foreign(
                'purchase_invoice_id',
                'spv_allocations_invoice_fk',
            )
                ->references('id')
                ->on('purchase_invoices')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(
                ['supplier_payment_voucher_id', 'purchase_invoice_id'],
                'supplier_payment_allocations_voucher_invoice_unique',
            );
            $table->index('purchase_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_voucher_allocations');
        Schema::dropIfExists('supplier_payment_vouchers');
    }
};
