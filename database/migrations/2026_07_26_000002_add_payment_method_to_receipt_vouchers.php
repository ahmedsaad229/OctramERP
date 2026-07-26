<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipt_vouchers', function (Blueprint $table): void {
            $table->string('payment_method', 30)
                ->default('cash')
                ->after('amount')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('receipt_vouchers', function (Blueprint $table): void {
            $table->dropIndex(['payment_method']);
            $table->dropColumn('payment_method');
        });
    }
};
