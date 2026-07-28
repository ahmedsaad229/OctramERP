<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number')->unique();
            $table->string('customer_order_number')->nullable();
            $table->foreignId('customer_id');
            $table->foreign('customer_id', 'fk_cpo_customer')
                ->references('id')->on('customers')->restrictOnDelete();
            $table->date('order_date')->index();
            $table->date('received_date')->nullable();
            $table->date('required_delivery_date')->nullable()->index();
            $table->date('actual_completion_date')->nullable();
            $table->string('delivery_location')->nullable();
            $table->string('project_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->decimal('execution_percentage', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'customer_order_number'], 'idx_cpo_customer_order');
        });

        Schema::create('customer_purchase_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_purchase_order_id');
            $table->foreign('customer_purchase_order_id', 'fk_cpo_item_order')
                ->references('id')->on('customer_purchase_orders')->cascadeOnDelete();
            $table->foreignId('item_id');
            $table->foreign('item_id', 'fk_cpo_item_product')
                ->references('id')->on('items')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('unit_id')->nullable();
            $table->foreign('unit_id', 'fk_cpo_item_unit')
                ->references('id')->on('units')->nullOnDelete();
            $table->decimal('ordered_quantity', 15, 2);
            $table->decimal('executed_quantity', 15, 2)->default(0);
            $table->decimal('remaining_quantity', 15, 2);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('line_subtotal', 15, 2)->nullable();
            $table->decimal('line_tax', 15, 2)->nullable();
            $table->decimal('line_total', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('customer_purchase_order_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_purchase_order_id');
            $table->foreign('customer_purchase_order_id', 'fk_cpo_attach_order')
                ->references('id')->on('customer_purchase_orders')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('file_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->nullable();
            $table->foreign('uploaded_by', 'fk_cpo_attach_user')
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customer_purchase_order_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_purchase_order_id');
            $table->foreign('customer_purchase_order_id', 'fk_cpo_follow_order')
                ->references('id')->on('customer_purchase_orders')->cascadeOnDelete();
            $table->date('follow_up_date');
            $table->string('event_type', 40);
            $table->text('note');
            $table->foreignId('created_by')->nullable();
            $table->foreign('created_by', 'fk_cpo_follow_user')
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customer_purchase_order_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_purchase_order_id');
            $table->foreign('customer_purchase_order_id', 'fk_cpo_exec_order')
                ->references('id')->on('customer_purchase_orders')->cascadeOnDelete();
            $table->foreignId('customer_purchase_order_item_id');
            $table->foreign('customer_purchase_order_item_id', 'fk_cpo_exec_item')
                ->references('id')->on('customer_purchase_order_items')->cascadeOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('source_item_id')->nullable();
            $table->decimal('executed_quantity', 15, 2);
            $table->date('execution_date');
            $table->timestamps();
            $table->unique(['source_type', 'source_id', 'customer_purchase_order_item_id'], 'cpo_execution_source_item_unique');
        });

        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->foreignId('customer_purchase_order_id')->nullable()->after('sales_quotation_id');
            $table->foreign('customer_purchase_order_id', 'fk_sales_inv_cpo')
                ->references('id')->on('customer_purchase_orders')->nullOnDelete();
        });
        Schema::table('sales_invoice_items', function (Blueprint $table): void {
            $table->foreignId('customer_purchase_order_item_id')->nullable()->after('sales_quotation_item_id');
            $table->foreign('customer_purchase_order_item_id', 'fk_sales_item_cpo_item')
                ->references('id')->on('customer_purchase_order_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table): void {
            $table->dropForeign('fk_sales_item_cpo_item');
            $table->dropColumn('customer_purchase_order_item_id');
        });
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->dropForeign('fk_sales_inv_cpo');
            $table->dropColumn('customer_purchase_order_id');
        });
        Schema::dropIfExists('customer_purchase_order_executions');
        Schema::dropIfExists('customer_purchase_order_follow_ups');
        Schema::dropIfExists('customer_purchase_order_attachments');
        Schema::dropIfExists('customer_purchase_order_items');
        Schema::dropIfExists('customer_purchase_orders');
    }
};
