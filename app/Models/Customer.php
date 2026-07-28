<?php

namespace App\Models;

use App\Models\Concerns\ProtectsDocumentDeletion;
use App\Support\Octram\Traits\HasCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasCode;
    use ProtectsDocumentDeletion;

    protected static string $codePrefix = 'CUS';

    protected $fillable = [
        'code',
        'name',
        'mobile',
        'phone',
        'email',
        'tax_number',
        'commercial_register',
        'country',
        'governorate',
        'city',
        'address',
        'opening_balance',
        'credit_limit',
        'active',
        'notes',
    ];

    public function customerPurchaseOrders(): HasMany
    {
        return $this->hasMany(CustomerPurchaseOrder::class);
    }
}
