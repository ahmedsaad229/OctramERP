<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPurchaseOrderAttachment extends BaseModel
{
    protected $fillable = ['original_name', 'stored_name', 'file_path', 'mime_type', 'file_size', 'uploaded_by'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerPurchaseOrder::class, 'customer_purchase_order_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
