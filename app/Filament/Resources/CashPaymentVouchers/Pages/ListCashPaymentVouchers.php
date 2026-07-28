<?php

namespace App\Filament\Resources\CashPaymentVouchers\Pages;

use App\Filament\Resources\CashPaymentVouchers\CashPaymentVoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashPaymentVouchers extends ListRecords
{
    protected static string $resource = CashPaymentVoucherResource::class;

    protected static ?string $title = 'سندات صرف النقدية';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('إضافة سند صرف نقدية')];
    }
}
