<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void { Schema::create('bank_checks',function(Blueprint $table):void{
  $table->id(); $table->string('document_number')->unique(); $table->string('type',20)->index();
  $table->string('check_number')->index(); $table->date('issue_date')->index(); $table->date('due_date')->index();
  $table->decimal('amount',18,2); $table->string('status',30)->index();
  $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
  $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
  $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
  $table->string('drawer_bank')->nullable(); $table->string('drawer_name')->nullable();
  $table->string('beneficiary_name')->nullable(); $table->date('cleared_date')->nullable();
  $table->string('reference_number')->nullable(); $table->text('notes')->nullable();
  $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
  $table->unique(['type','check_number','drawer_bank']);
 }); }
 public function down():void { Schema::dropIfExists('bank_checks'); }
};