<?php

namespace App\Filament\Resources\DueObligations\Pages;

use App\Filament\Resources\DueObligations\DueObligationResource;
use App\Filament\Resources\DueObligations\Widgets\DueObligationStats;
use App\Models\DueObligation;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListDueObligations extends ListRecords
{
    protected static string $resource = DueObligationResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            DueObligationStats::class,
        ];
    }

    protected function resolveTableRecord(?string $key): Model|array|null
    {
        if (! $key || ! str_contains($key, ':')) {
            return null;
        }

        [$type, $sourceId] = explode(':', $key, 2);

        return DueObligation::queryUnified()
            ->where('source_type', $type)
            ->where('source_id', $sourceId)
            ->first();
    }
}
