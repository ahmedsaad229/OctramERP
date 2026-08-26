<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table): void {
            $table->string('supplier_document_type', 30)
                ->default('invoice')
                ->after('supplier_purchase_order_id');
        });

        // الفواتير القديمة التي لا تحتوي رقم فاتورة مورد نعتبرها "بيان أسعار".
        DB::table('purchase_invoices')
            ->whereNull('invoice_number')
            ->update(['supplier_document_type' => 'price_statement']);
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table): void {
            $table->dropColumn('supplier_document_type');
        });
    }
};
