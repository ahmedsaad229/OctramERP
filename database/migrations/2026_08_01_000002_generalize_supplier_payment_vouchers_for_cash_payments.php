<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_payment_vouchers', function (Blueprint $table): void {
            $table->string('payment_type', 20)->default('supplier')->after('voucher_date')->index();
            $table->string('payment_reason', 40)->nullable()->after('payment_method')->index();
            $table->string('beneficiary_name')->nullable()->after('payment_reason');
            $table->string('reference_number')->nullable()->after('beneficiary_name')->index();
        });

        Schema::table('supplier_payment_vouchers', function (Blueprint $table): void {
            $table->unsignedBigInteger('supplier_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_payment_vouchers', function (Blueprint $table): void {
            $table->unsignedBigInteger('supplier_id')->nullable(false)->change();
        });

        Schema::table('supplier_payment_vouchers', function (Blueprint $table): void {
            $table->dropColumn(['payment_type', 'payment_reason', 'beneficiary_name', 'reference_number']);
        });
    }
};
