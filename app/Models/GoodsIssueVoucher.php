<?php

namespace App\Models;

use App\Support\Octram\Traits\HasCode;
use App\Services\DocumentNumberService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsIssueVoucher extends BaseModel
{
    use HasCode;

    protected static string $codePrefix = 'GIV';

    protected static string $documentType = DocumentNumberService::GOODS_ISSUE;

    protected $fillable = [
        'code',
        'voucher_date',
        'warehouse_id',
        'notes',
        'posted',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'posted' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsIssueItem::class);
    }
}
