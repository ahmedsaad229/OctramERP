<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('octram_entries', function (Blueprint $table): void {
            $table->decimal('purchase_quantity', 15, 3)
                ->default(1)
                ->after('purchase_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('octram_entries', function (Blueprint $table): void {
            $table->dropColumn('purchase_quantity');
        });
    }
};
