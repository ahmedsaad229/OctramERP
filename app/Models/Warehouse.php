<?php

namespace App\Models;

use App\Models\Concerns\ProtectsDocumentDeletion;
use App\Support\Octram\Traits\HasCode;

class Warehouse extends BaseModel
{
    use HasCode;
    use ProtectsDocumentDeletion;

    protected static string $codePrefix = 'WAR';

    protected $fillable = [
        'code',
        'name',
        'manager',
        'phone',
        'address',
        'active',
        'description',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
