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
        'contact_person',
        'contact_job_title',
        'contact_mobile',
        'contact_email',
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

    public function followUps(): HasMany
    {
        return $this->hasMany(CustomerFollowUp::class);
    }

    public function customerPurchaseOrders(): HasMany
    {
        return $this->hasMany(CustomerPurchaseOrder::class);
    }
}
