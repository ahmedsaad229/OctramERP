<?php

namespace App\Models;

use App\Services\DocumentNumberService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFollowUp extends Model
{
    public const TYPE_CALL = 'call';
    public const TYPE_VISIT = 'visit';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_WHATSAPP = 'whatsapp';
    public const TYPE_EMAIL = 'email';
    public const TYPE_QUOTATION = 'quotation_follow_up';
    public const TYPE_COLLECTION = 'collection';
    public const TYPE_COMPLAINT = 'complaint';
    public const TYPE_NOTE = 'note';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_POSTPONED = 'postponed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_ANSWER = 'no_answer';

    protected $fillable = [
        'follow_up_number',
        'customer_id',
        'sales_responsible_id',
        'created_by',
        'type',
        'status',
        'priority',
        'scheduled_at',
        'completed_at',
        'contact_person',
        'subject',
        'discussion',
        'customer_feedback',
        'result',
        'next_action',
        'next_follow_up_at',
        'visit_location',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $followUp): void {
            $followUp->follow_up_number ??= app(DocumentNumberService::class)
                ->generate(DocumentNumberService::CUSTOMER_FOLLOW_UP);

            $followUp->created_by ??= auth()->id();
            $followUp->sales_responsible_id ??= auth()->id();

            if (
                $followUp->status === self::STATUS_COMPLETED
                && blank($followUp->completed_at)
            ) {
                $followUp->completed_at = now();
            }
        });

        static::updating(function (self $followUp): void {
            if (
                $followUp->status === self::STATUS_COMPLETED
                && blank($followUp->completed_at)
            ) {
                $followUp->completed_at = now();
            }

            if ($followUp->status !== self::STATUS_COMPLETED) {
                $followUp->completed_at = null;
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesResponsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_responsible_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && $this->scheduled_at
            && $this->scheduled_at->isPast();
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now());
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_CALL => 'مكالمة هاتفية',
            self::TYPE_VISIT => 'زيارة',
            self::TYPE_MEETING => 'اجتماع',
            self::TYPE_WHATSAPP => 'واتساب',
            self::TYPE_EMAIL => 'بريد إلكتروني',
            self::TYPE_QUOTATION => 'متابعة عرض سعر',
            self::TYPE_COLLECTION => 'متابعة تحصيل',
            self::TYPE_COMPLAINT => 'شكوى عميل',
            self::TYPE_NOTE => 'ملاحظة داخلية',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_SCHEDULED => 'مجدولة',
            self::STATUS_COMPLETED => 'تمت',
            self::STATUS_POSTPONED => 'مؤجلة',
            self::STATUS_CANCELLED => 'ملغاة',
            self::STATUS_NO_ANSWER => 'لم يتم التواصل',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            'low' => 'منخفضة',
            'normal' => 'عادية',
            'high' => 'عالية',
            'urgent' => 'عاجلة',
        ];
    }

    public static function resultOptions(): array
    {
        return [
            'contacted' => 'تم التواصل بنجاح',
            'no_answer' => 'لم يرد',
            'call_later' => 'طلب الاتصال لاحقًا',
            'interested' => 'مهتم',
            'not_interested' => 'غير مهتم حاليًا',
            'quotation_requested' => 'طلب عرض سعر',
            'quotation_update' => 'طلب تعديل عرض السعر',
            'visit_requested' => 'طلب زيارة',
            'sample_requested' => 'طلب عينة',
            'initial_approval' => 'موافقة مبدئية',
            'management_approval' => 'بانتظار موافقة الإدارة',
            'agreed' => 'تم الاتفاق',
            'complaint_action' => 'شكوى تحتاج إجراء',
            'other' => 'نتيجة أخرى',
        ];
    }
}
