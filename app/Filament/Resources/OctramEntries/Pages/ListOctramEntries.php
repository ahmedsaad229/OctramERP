<?php

namespace App\Filament\Resources\OctramEntries\Pages;

use App\Filament\Resources\OctramEntries\OctramEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOctramEntries extends ListRecords
{
    protected static string $resource = OctramEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة تقرير أوكترام'),
        ];
    }
}