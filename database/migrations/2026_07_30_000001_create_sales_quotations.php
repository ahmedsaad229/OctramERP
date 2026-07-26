<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->date('quotation_date')->index();
            $table->date('valid_until')->nullable()->index();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tax_type')->default('none')->index();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('sales_quotation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['sales_quotation_id', 'item_id']);
        });

        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->foreignId('sales_quotation_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('sales_invoice_items', function (Blueprint $table): void {
            $table->foreignId('sales_quotation_item_id')
                ->nullable()
                ->after('item_id')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->after('sales_quotation_item_id')->constrained()->nullOnDelete();
            $table->decimal('discount_amount', 15, 2)->default(0)->after('unit_price');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('discount_amount');
            $table->text('notes')->nullable()->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropConstrainedForeignId('sales_quotation_item_id');
            $table->dropColumn(['discount_amount', 'tax_amount', 'notes']);
        });
        Schema::table('sales_invoices', fn (Blueprint $table) => $table->dropConstrainedForeignId('sales_quotation_id'));
        Schema::dropIfExists('sales_quotation_items');
        Schema::dropIfExists('sales_quotations');
    }
};
