<?php

namespace App\Filament\Resources\ReceiptVouchers\Pages;

use App\Filament\Resources\ReceiptVouchers\ReceiptVoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReceiptVouchers extends ListRecords
{
    protected static string $resource = ReceiptVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة سند قبض'),
        ];
    }
}
