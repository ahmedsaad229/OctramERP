<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->date('transaction_date');
            $table->string('type', 50);
            $table->decimal('amount', 15, 2);
            $table->enum('direction', ['debit', 'credit']);
            $table->morphs('source');
            $table->string('document_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
            $table->index(['treasury_id', 'transaction_date']);
            $table->index(['type', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_transactions');
    }
};
