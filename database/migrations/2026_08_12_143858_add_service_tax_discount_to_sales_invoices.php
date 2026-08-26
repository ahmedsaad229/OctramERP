<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->boolean('service_tax_discount_enabled')
                ->default(false);

            $table->decimal('service_tax_discount_amount', 15, 2)
                ->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'service_tax_discount_enabled',
                'service_tax_discount_amount',
            ]);
        });
    }
};