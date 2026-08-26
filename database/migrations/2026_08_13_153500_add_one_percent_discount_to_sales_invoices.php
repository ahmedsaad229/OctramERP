<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales_invoices', 'one_percent_discount_enabled')) {
            Schema::table('sales_invoices', function (Blueprint $table): void {
                $table->boolean('one_percent_discount_enabled')
                    ->default(false);
            });
        }

        if (! Schema::hasColumn('sales_invoices', 'one_percent_discount_amount')) {
            Schema::table('sales_invoices', function (Blueprint $table): void {
                $table->decimal('one_percent_discount_amount', 15, 2)
                    ->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_invoices', 'one_percent_discount_amount')) {
            Schema::table('sales_invoices', function (Blueprint $table): void {
                $table->dropColumn('one_percent_discount_amount');
            });
        }

        if (Schema::hasColumn('sales_invoices', 'one_percent_discount_enabled')) {
            Schema::table('sales_invoices', function (Blueprint $table): void {
                $table->dropColumn('one_percent_discount_enabled');
            });
        }
    }
};