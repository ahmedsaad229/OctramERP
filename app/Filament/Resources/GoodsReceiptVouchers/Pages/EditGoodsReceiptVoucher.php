<?php

namespace App\Filament\Resources\GoodsReceiptVouchers\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\GoodsReceiptVouchers\GoodsReceiptVoucherResource;
use App\Models\GoodsReceiptVoucher;
use App\Services\Inventory\GoodsReceiptService;
use Filament\Resources\Pages\EditRecord;

class EditGoodsReceiptVoucher extends EditRecord
{
    protected static string $resource = GoodsReceiptVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProtectedDeleteAction::make()
                ->using(fn (GoodsReceiptVoucher $record): bool => app(GoodsReceiptService::class)->delete($record)),
        ];
    }

    protected function afterSave(): void
    {
        app(GoodsReceiptService::class)->post($this->record);
    }
}
