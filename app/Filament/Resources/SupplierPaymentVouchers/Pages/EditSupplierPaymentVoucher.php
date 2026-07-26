<?php

namespace App\Filament\Resources\SupplierPaymentVouchers\Pages;

use App\Filament\Resources\SupplierPaymentVouchers\SupplierPaymentVoucherResource;
use App\Models\SupplierPaymentVoucher;
use App\Services\SupplierPaymentVoucherService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSupplierPaymentVoucher extends EditRecord
{
    protected static string $resource = SupplierPaymentVoucherResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['purchase_invoice_id'] = $this->getRecord()
            ->allocations()
            ->value('purchase_invoice_id');

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(SupplierPaymentVoucherService::class)->update($record, $data);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('حفظ التعديلات');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تعديل وإعادة ترحيل سند الصرف بنجاح.';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('هل تريد حذف سند صرف المورد؟')
                ->successNotificationTitle('تم حذف سند الصرف بنجاح.')
                ->using(fn (SupplierPaymentVoucher $record): bool => app(
                    SupplierPaymentVoucherService::class,
                )->delete($record)),
        ];
    }
}
