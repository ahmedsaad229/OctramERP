<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipt_vouchers', function (Blueprint $table): void {
            $table->string('receipt_type', 20)->default('customer')->after('document_number')->index();
            $table->string('receipt_reason', 50)->nullable()->after('payment_method');
            $table->string('payer_name')->nullable()->after('receipt_reason');
            $table->string('reference_number')->nullable()->after('payer_name')->index();
            $table->foreignId('customer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('receipt_vouchers', function (Blueprint $table): void {
            $table->dropIndex(['receipt_type']);
            $table->dropIndex(['reference_number']);
            $table->dropColumn(['receipt_type', 'receipt_reason', 'payer_name', 'reference_number']);
            $table->foreignId('customer_id')->nullable(false)->change();
        });
    }
};
