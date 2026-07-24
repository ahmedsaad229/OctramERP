<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();
            $table->morphs('party');
            $table->string('transaction_type', 50);
            $table->nullableMorphs('source');
            $table->string('reference_no')->nullable();
            $table->date('transaction_date');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
            $table->index(['party_type', 'party_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
