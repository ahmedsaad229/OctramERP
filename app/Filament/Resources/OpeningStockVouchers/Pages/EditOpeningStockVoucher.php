<?php

namespace App\Filament\Resources\OpeningStockVouchers\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\OpeningStockVouchers\OpeningStockVoucherResource;
use App\Models\OpeningStockVoucher;
use App\Services\Inventory\OpeningStockService;
use Filament\Resources\Pages\EditRecord;

class EditOpeningStockVoucher extends EditRecord
{
    protected static string $resource = OpeningStockVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProtectedDeleteAction::make()
                ->using(fn (OpeningStockVoucher $record): bool => app(OpeningStockService::class)->delete($record)),
        ];
    }

    protected function afterSave(): void
    {
        app(OpeningStockService::class)->post($this->record);
    }
}
