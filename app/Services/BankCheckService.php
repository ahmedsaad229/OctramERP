<?php
namespace App\Services;
use App\Models\BankCheck; use App\Models\BankTransaction; use Illuminate\Support\Facades\DB; use Illuminate\Validation\ValidationException;
class BankCheckService {
 public function create(array $data):BankCheck{return DB::transaction(function()use($data){$m=BankCheck::query()->create($data);$this->syncBankMovement($m);return $m;});}
 public function update(BankCheck $m,array $data):BankCheck{return DB::transaction(function()use($m,$data){$m->update($data);$this->syncBankMovement($m->refresh());return $m;});}
 public function delete(BankCheck $m):void{DB::transaction(function()use($m){app(BankTransactionService::class)->deleteForSource($m);$m->delete();});}
 public function syncBankMovement(BankCheck $m):void {
  app(BankTransactionService::class)->deleteForSource($m);
  $final=$m->type===BankCheck::TYPE_INCOMING?BankCheck::STATUS_COLLECTED:BankCheck::STATUS_CASHED;
  if($m->status!==$final)return;
  if(!$m->bank_account_id)throw ValidationException::withMessages(['bank_account_id'=>'يجب اختيار الحساب البنكي عند تحصيل أو صرف الشيك.']);
  app(BankTransactionService::class)->replaceForSource($m->bankAccount,$m,$m->cleared_date?:now()->toDateString(),$m->type===BankCheck::TYPE_INCOMING?BankTransaction::TYPE_RECEIPT:BankTransaction::TYPE_PAYMENT,(float)$m->amount,$m->type===BankCheck::TYPE_INCOMING?BankTransaction::DIRECTION_DEBIT:BankTransaction::DIRECTION_CREDIT,$m->document_number,$m->check_number,$m->notes);
 }
}