<?php

namespace App\Filament\Resources\SalesQuotations\Pages;

use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use App\Filament\Resources\SalesQuotations\Widgets\SalesQuotationStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesQuotations extends ListRecords
{
    protected static string $resource = SalesQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('إضافة عرض سعر')];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SalesQuotationStats::class,
        ];
    }
}
