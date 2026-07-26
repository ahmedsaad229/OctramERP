<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_purchase_orders', function (Blueprint $table): void {
            $table->string('tax_type')->default('none')->after('discount_amount');
        });

        foreach (['purchase_invoices', 'sales_invoices'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('due_date');
                $table->string('tax_type')->default('none')->after('discount_amount');
                $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('supplier_purchase_orders', fn (Blueprint $table) => $table->dropColumn('tax_type'));

        foreach (['purchase_invoices', 'sales_invoices'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn([
                'discount_amount',
                'tax_type',
                'tax_amount',
            ]));
        }
    }
};
