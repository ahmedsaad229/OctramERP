<?php

namespace App\Filament\Resources\ReceiptVouchers\Pages;

use App\Filament\Resources\ReceiptVouchers\ReceiptVoucherResource;
use App\Services\ReceiptVoucherService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateReceiptVoucher extends CreateRecord
{
    protected static string $resource = ReceiptVoucherResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return app(ReceiptVoucherService::class)->create($data);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('حفظ سند القبض');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم حفظ سند القبض بنجاح.';
    }
}
