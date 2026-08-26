<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('swift_code', 20)->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('account_name');
            $table->string('branch_name')->nullable();
            $table->string('account_number')->nullable()->index();
            $table->string('iban', 60)->nullable()->index();
            $table->string('currency', 3)->default('EGP');
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number')->unique();
            $table->date('transfer_date')->index();
            $table->foreignId('from_bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->foreignId('to_bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->decimal('fees', 18, 2)->default(0);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->date('transaction_date')->index();
            $table->string('type', 30)->index();
            $table->string('direction', 10)->index();
            $table->decimal('amount', 18, 2);
            $table->nullableMorphs('source');
            $table->string('document_number')->nullable()->index();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['bank_account_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_transfers');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('banks');
    }
};
