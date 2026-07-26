<?php

namespace App\Models;

use App\Models\Concerns\ProtectsDocumentDeletion;
use App\Support\Octram\Traits\HasCode;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasCode;
    use ProtectsDocumentDeletion;

    protected static string $codePrefix = 'UNT';

    protected $fillable = [
        'code',
        'name',
        'short_name',
        'description',
        'active',
    ];
}
