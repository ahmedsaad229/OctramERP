<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->string('entry_type', 20)
                ->default('automatic')
                ->after('entry_date')
                ->index();

            $table->foreignId('updated_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->unsignedBigInteger('source_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['entry_type', 'updated_by']);
        });
    }
};
