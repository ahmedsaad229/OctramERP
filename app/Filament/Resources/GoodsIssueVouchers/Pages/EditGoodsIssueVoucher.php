<?php

namespace App\Filament\Resources\GoodsIssueVouchers\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\GoodsIssueVouchers\GoodsIssueVoucherResource;
use App\Models\GoodsIssueVoucher;
use App\Services\Inventory\GoodsIssueService;
use Filament\Resources\Pages\EditRecord;

class EditGoodsIssueVoucher extends EditRecord
{
    protected static string $resource = GoodsIssueVoucherResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected function getHeaderActions(): array
    {
        return [
            ProtectedDeleteAction::make()
                ->using(fn (GoodsIssueVoucher $record): bool => app(GoodsIssueService::class)->delete($record)),
        ];
    }

    protected function afterSave(): void
    {
        app(GoodsIssueService::class)->post($this->record);
    }
}
