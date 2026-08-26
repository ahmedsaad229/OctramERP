<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('purchase_invoice_items', 'tax_exempt')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table): void {
                $table->boolean('tax_exempt')
                    ->default(false)
                    ->after('unit_cost');
            });
        }

        if (! Schema::hasColumn('purchase_invoice_items', 'tax_amount')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table): void {
                $table->decimal('tax_amount', 14, 2)
                    ->default(0)
                    ->after('tax_exempt');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_invoice_items', 'tax_amount')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table): void {
                $table->dropColumn('tax_amount');
            });
        }

        if (Schema::hasColumn('purchase_invoice_items', 'tax_exempt')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table): void {
                $table->dropColumn('tax_exempt');
            });
        }
    }
};
