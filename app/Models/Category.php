<?php

namespace App\Models;

use App\Models\Concerns\ProtectsDocumentDeletion;
use App\Support\Octram\Traits\HasCode;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasCode;
    use ProtectsDocumentDeletion;

    protected static string $codePrefix = 'CAT';

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'description',
        'active',
    ];
}
