<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPurchaseOrderFollowUp extends BaseModel
{
    protected $fillable = ['follow_up_date', 'event_type', 'note', 'created_by'];

    protected $casts = ['follow_up_date' => 'date'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerPurchaseOrder::class, 'customer_purchase_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function eventOptions(): array
    {
        return ['received' => 'تم استلام أمر التوريد', 'reviewed' => 'تمت المراجعة', 'customer_contact' => 'تواصل مع العميل', 'procurement_started' => 'بدأ تدبير الأصناف', 'goods_available' => 'الأصناف متاحة', 'execution_started' => 'بدأ التنفيذ', 'partial_delivery' => 'تم تنفيذ جزء من الأمر', 'completed' => 'تم التنفيذ بالكامل', 'delayed' => 'يوجد تأخير', 'suspended' => 'تم إيقاف التنفيذ', 'cancelled' => 'تم الإلغاء', 'other' => 'متابعة أخرى'];
    }
}
