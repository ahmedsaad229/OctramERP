<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
class BankCheck extends BaseModel {
 public const TYPE_INCOMING='incoming'; public const TYPE_OUTGOING='outgoing';
 public const STATUS_IN_HAND='in_hand'; public const STATUS_DEPOSITED='deposited'; public const STATUS_COLLECTED='collected'; public const STATUS_RETURNED='returned'; public const STATUS_CANCELLED='cancelled';
 public const STATUS_PENDING_DELIVERY='pending_delivery'; public const STATUS_DELIVERED='delivered'; public const STATUS_CASHED='cashed';
 protected $fillable=['document_number','type','check_number','issue_date','due_date','amount','status','bank_account_id','customer_id','supplier_id','drawer_bank','drawer_name','beneficiary_name','cleared_date','reference_number','notes','created_by'];
 protected $casts=['issue_date'=>'date','due_date'=>'date','cleared_date'=>'date','amount'=>'decimal:2'];
 protected static function booted():void {
  static::creating(function(self $m):void{ if(blank($m->document_number)){ $n=((int)static::query()->max('id'))+1; $m->document_number=($m->type===self::TYPE_OUTGOING?'CHO':'CHI').str_pad((string)$n,5,'0',STR_PAD_LEFT);} $m->created_by ??= auth()->id(); });
  static::deleting(function(self $m):void{ if(in_array($m->status,[self::STATUS_COLLECTED,self::STATUS_CASHED],true)) throw ValidationException::withMessages(['check'=>'لا يمكن حذف شيك تم تحصيله أو صرفه.']); });
 }
 public function bankAccount():BelongsTo{return $this->belongsTo(BankAccount::class);} public function customer():BelongsTo{return $this->belongsTo(Customer::class);} public function supplier():BelongsTo{return $this->belongsTo(Supplier::class);} public function creator():BelongsTo{return $this->belongsTo(User::class,'created_by');}
 public static function typeOptions():array{return[self::TYPE_INCOMING=>'وارد',self::TYPE_OUTGOING=>'صادر'];}
 public static function statusOptions(?string $type=null):array { if($type===self::TYPE_INCOMING)return[self::STATUS_IN_HAND=>'بالخزينة',self::STATUS_DEPOSITED=>'مودع بالبنك',self::STATUS_COLLECTED=>'تم التحصيل',self::STATUS_RETURNED=>'مرتد',self::STATUS_CANCELLED=>'ملغي']; if($type===self::TYPE_OUTGOING)return[self::STATUS_PENDING_DELIVERY=>'تحت التسليم',self::STATUS_DELIVERED=>'تم التسليم',self::STATUS_CASHED=>'تم الصرف',self::STATUS_CANCELLED=>'ملغي']; return array_merge(self::statusOptions(self::TYPE_INCOMING),self::statusOptions(self::TYPE_OUTGOING)); }
 public function partyName():string{return $this->type===self::TYPE_INCOMING?($this->customer?->name??$this->drawer_name??'—'):($this->supplier?->name??$this->beneficiary_name??'—');}
}