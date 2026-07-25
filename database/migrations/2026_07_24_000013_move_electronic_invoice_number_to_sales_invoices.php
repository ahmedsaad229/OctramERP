<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('electronic_invoice_number')
                ->nullable()
                ->after('document_number')
                ->index();
        });

        DB::statement(
            'UPDATE sales_invoices si
             INNER JOIN (
                 SELECT sales_invoice_id, MAX(electronic_invoice_number) AS electronic_invoice_number
                 FROM receipt_voucher_allocations
                 GROUP BY sales_invoice_id
             ) allocations ON allocations.sales_invoice_id = si.id
             SET si.electronic_invoice_number = allocations.electronic_invoice_number',
        );

        Schema::table('receipt_voucher_allocations', function (Blueprint $table) {
            $table->dropColumn('electronic_invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('receipt_voucher_allocations', function (Blueprint $table) {
            $table->unsignedBigInteger('electronic_invoice_number')->nullable()->after('sales_invoice_id');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex(['electronic_invoice_number']);
            $table->dropColumn('electronic_invoice_number');
        });
    }
};
