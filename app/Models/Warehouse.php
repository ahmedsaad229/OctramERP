<?php

namespace App\Models;

use App\Support\Octram\Traits\HasCode;

class Warehouse extends BaseModel
{
    use HasCode;

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