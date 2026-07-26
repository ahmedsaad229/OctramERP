<?php

namespace App\Filament\Resources\SupplierPaymentVouchers\Pages;

use App\Filament\Resources\SupplierPaymentVouchers\SupplierPaymentVoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierPaymentVouchers extends ListRecords
{
    protected static string $resource = SupplierPaymentVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
