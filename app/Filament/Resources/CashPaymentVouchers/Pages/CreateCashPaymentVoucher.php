<?php

namespace App\Filament\Resources\CashPaymentVouchers\Pages;

use App\Filament\Resources\CashPaymentVouchers\CashPaymentVoucherResource;
use App\Services\SupplierPaymentVoucherService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCashPaymentVoucher extends CreateRecord
{
    protected static string $resource = CashPaymentVoucherResource::class;

    protected static ?string $title = 'إضافة سند صرف نقدية';

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SupplierPaymentVoucherService::class)->create($data);
    }
}
