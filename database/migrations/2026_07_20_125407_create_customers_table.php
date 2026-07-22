<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

$table->string('code')->unique();
$table->string('name');
$table->string('mobile')->nullable();
$table->string('phone')->nullable();
$table->string('email')->nullable();

$table->string('tax_number')->nullable();
$table->string('commercial_register')->nullable();

$table->string('country')->default('Egypt');
$table->string('governorate')->nullable();
$table->string('city')->nullable();

$table->text('address')->nullable();

$table->decimal('opening_balance', 15, 2)->default(0);
$table->decimal('credit_limit', 15, 2)->default(0);

$table->boolean('active')->default(true);

$table->text('notes')->nullable();

$table->timestamps();
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
