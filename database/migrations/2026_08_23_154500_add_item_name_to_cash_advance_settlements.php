<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_advance_settlements', function (Blueprint $table): void {
            $table->string('item_name')
                ->nullable()
                ->after('settlement_date');
        });
    }

    public function down(): void
    {
        Schema::table('cash_advance_settlements', function (Blueprint $table): void {
            $table->dropColumn('item_name');
        });
    }
};
