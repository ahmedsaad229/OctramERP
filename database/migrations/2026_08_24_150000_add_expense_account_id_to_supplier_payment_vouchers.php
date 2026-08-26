<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_payment_vouchers', function (Blueprint $table): void {
            $table->foreignId('expense_account_id')
                ->nullable()
                ->after('payment_reason')
                ->constrained('accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_payment_vouchers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('expense_account_id');
        });
    }
};
