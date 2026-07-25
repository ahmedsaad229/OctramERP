<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->string('payment_type', 20)->default('cash')->after('warehouse_id');
            $table->date('due_date')->nullable()->after('payment_type');
            $table->index('payment_type');
            $table->index('due_date');
        });

        Schema::table('purchase_invoices', function (Blueprint $table): void {
            $table->string('payment_type', 20)->default('cash')->after('warehouse_id');
            $table->date('due_date')->nullable()->after('payment_type');
            $table->index('payment_type');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->dropIndex(['payment_type']);
            $table->dropIndex(['due_date']);
            $table->dropColumn(['payment_type', 'due_date']);
        });

        Schema::table('purchase_invoices', function (Blueprint $table): void {
            $table->dropIndex(['payment_type']);
            $table->dropIndex(['due_date']);
            $table->dropColumn(['payment_type', 'due_date']);
        });
    }
};
