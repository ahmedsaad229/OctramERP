<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait BuildsProcurementSchema
{
    protected function buildProcurementSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
        Schema::create('company_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name')->default('Octram ERP');
            $table->string('default_tax_type')->default('vat_14');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('warehouses', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('unit_id')->nullable();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->decimal('minimum_stock', 15, 2)->default(0);
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('stock_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id');
            $table->foreignId('item_id');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('average_cost', 15, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('stock_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id');
            $table->foreignId('item_id');
            $table->string('transaction_type');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->date('transaction_date');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('party_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('party_type');
            $table->unsignedBigInteger('party_id');
            $table->string('transaction_type');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference_no')->nullable();
            $table->date('transaction_date');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('treasury_transactions', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
        Schema::create('document_number_counters', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type')->unique();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });
        Schema::create('purchase_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->date('request_date');
            $table->date('required_date')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->string('department')->nullable();
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('purchase_request_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_request_id');
            $table->foreignId('item_id');
            $table->foreignId('unit_id');
            $table->decimal('requested_quantity', 15, 2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('supplier_purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->date('order_date');
            $table->foreignId('supplier_id');
            $table->foreignId('purchase_request_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->string('payment_type')->nullable();
            $table->date('due_date')->nullable();
            $table->string('supplier_reference')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->string('tax_type')->default('none');
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('supplier_purchase_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_purchase_order_id');
            $table->foreignId('purchase_request_item_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('unit_id');
            $table->decimal('ordered_quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('purchase_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('supplier_id');
            $table->foreignId('supplier_purchase_order_id')->nullable();
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->foreignId('warehouse_id');
            $table->string('payment_type')->default('cash');
            $table->date('due_date')->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->string('tax_type')->default('none');
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('posted')->default(false);
            $table->timestamps();
        });
        Schema::create('purchase_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_invoice_id');
            $table->foreignId('supplier_purchase_order_item_id')->nullable();
            $table->foreignId('item_id');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
}
