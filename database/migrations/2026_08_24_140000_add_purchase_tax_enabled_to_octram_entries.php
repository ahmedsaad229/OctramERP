<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('octram_entries', function (Blueprint $table): void {
            $table->boolean('purchase_tax_enabled')
                ->default(false)
                ->after('purchase_price');
        });
    }

    public function down(): void
    {
        Schema::table('octram_entries', function (Blueprint $table): void {
            $table->dropColumn('purchase_tax_enabled');
        });
    }
};
