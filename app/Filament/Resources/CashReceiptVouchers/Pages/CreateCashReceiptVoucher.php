<?php

namespace App\Filament\Resources\CashReceiptVouchers\Pages;

use App\Filament\Resources\CashReceiptVouchers\CashReceiptVoucherResource;
use App\Services\ReceiptVoucherService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCashReceiptVoucher extends CreateRecord
{
    protected static string $resource = CashReceiptVoucherResource::class;

    protected static ?string $title = 'إضافة سند استلام نقدية';

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return app(ReceiptVoucherService::class)->create($data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء سند الاستلام النقدي بنجاح.';
    }
}
