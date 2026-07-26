<?php

namespace App\Models;

use App\Models\Concerns\ProtectsDocumentDeletion;
use App\Support\Octram\Traits\HasCode;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasCode;
    use ProtectsDocumentDeletion;

    protected static string $codePrefix = 'SUP';

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
}
