<?php

namespace App\Models;

use App\Models\Concerns\LocksClosedFiscalPeriods;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use LocksClosedFiscalPeriods;
}
