<?php

namespace App\Support\Octram\Traits;

use App\Services\DocumentNumberService;

trait HasCode
{
    protected static function bootHasCode(): void
    {
        static::creating(function ($model) {

            if (! empty($model->code)) {
                return;
            }

            if (property_exists(static::class, 'documentType')) {
                $model->code = app(DocumentNumberService::class)->generate(static::$documentType);

                return;
            }

            if (! property_exists(static::class, 'codePrefix')) {
                throw new \Exception(static::class . ' must define $codePrefix');
            }

            $nextId = (static::max('id') ?? 0) + 1;

            $model->code = static::$codePrefix . '-' . str_pad(
                $nextId,
                5,
                '0',
                STR_PAD_LEFT
            );
        });
    }
}
