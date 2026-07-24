<?php

namespace App\Filament\Resources\GoodsIssueVouchers\Pages;

use App\Filament\Resources\GoodsIssueVouchers\GoodsIssueVoucherResource;
use App\Services\Inventory\GoodsIssueService;
use Filament\Resources\Pages\CreateRecord;

class CreateGoodsIssueVoucher extends CreateRecord
{
    protected static string $resource = GoodsIssueVoucherResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected function afterCreate(): void
    {
        app(GoodsIssueService::class)->post($this->record);
    }
}
