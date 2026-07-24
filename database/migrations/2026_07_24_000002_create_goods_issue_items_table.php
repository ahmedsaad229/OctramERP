<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_issue_voucher_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_issue_items');
    }
};
