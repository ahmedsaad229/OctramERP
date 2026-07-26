<?php

namespace App\Filament\Resources\DueObligations\Pages;

use App\Filament\Resources\DueObligations\DueObligationResource;
use App\Filament\Resources\DueObligations\Widgets\DueObligationStats;
use App\Models\DueObligation;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
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

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل'),
            'customers' => Tab::make('العملاء')
                ->modifyQueryUsing(fn ($query) => $query->where('source_type', DueObligation::TYPE_SALE)),
            'suppliers' => Tab::make('الموردون')
                ->modifyQueryUsing(fn ($query) => $query->where('source_type', DueObligation::TYPE_PURCHASE)),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'all';
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
