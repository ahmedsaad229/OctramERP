<?php

namespace App\Filament\Resources\GoodsIssueVouchers\Pages;

use App\Filament\Resources\GoodsIssueVouchers\GoodsIssueVoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGoodsIssueVouchers extends ListRecords
{
    protected static string $resource = GoodsIssueVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
