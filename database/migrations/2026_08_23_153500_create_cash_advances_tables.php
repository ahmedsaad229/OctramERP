<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_advances', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number')->nullable()->unique();
            $table->date('advance_date');
            $table->string('recipient_name');
            $table->string('purpose')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->decimal('total_returned', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_advance_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cash_advance_id')
                ->constrained('cash_advances')
                ->cascadeOnDelete();

            $table->date('settlement_date');
            $table->string('type', 20);
            $table->string('description')->nullable();
            $table->string('document_number')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_advance_settlements');
        Schema::dropIfExists('cash_advances');
    }
};
