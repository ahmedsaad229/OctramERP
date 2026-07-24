<?php

namespace App\Filament\Resources\GoodsIssueVouchers\Pages;

use App\Filament\Resources\GoodsIssueVouchers\GoodsIssueVoucherResource;
use App\Services\Inventory\GoodsIssueService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGoodsIssueVoucher extends EditRecord
{
    protected static string $resource = GoodsIssueVoucherResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(GoodsIssueService::class)->post($this->record);
    }
}
