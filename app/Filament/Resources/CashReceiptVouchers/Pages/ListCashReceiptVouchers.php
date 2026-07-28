<?php

namespace App\Filament\Resources\CashReceiptVouchers\Pages;

use App\Filament\Resources\CashReceiptVouchers\CashReceiptVoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashReceiptVouchers extends ListRecords
{
    protected static string $resource = CashReceiptVoucherResource::class;

    protected static ?string $title = 'سندات استلام النقدية';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('إضافة سند استلام نقدية')];
    }
}
