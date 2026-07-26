<?php

namespace App\Filament\Resources\SupplierPaymentVouchers\Pages;

use App\Filament\Resources\SupplierPaymentVouchers\SupplierPaymentVoucherResource;
use App\Services\SupplierPaymentVoucherService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSupplierPaymentVoucher extends CreateRecord
{
    protected static string $resource = SupplierPaymentVoucherResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SupplierPaymentVoucherService::class)->create($data);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('حفظ سند الصرف');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم حفظ وترحيل سند الصرف بنجاح.';
    }
}
