<?php

namespace App\Models\Concerns;

use App\Services\Documents\DocumentDeletionGuard;

trait ProtectsDocumentDeletion
{
    public static function bootProtectsDocumentDeletion(): void
    {
        static::deleting(fn ($model) => app(DocumentDeletionGuard::class)->assertCanDelete($model));
    }
}
