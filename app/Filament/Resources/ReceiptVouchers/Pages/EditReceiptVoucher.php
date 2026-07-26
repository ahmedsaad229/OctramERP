<?php

namespace App\Filament\Resources\ReceiptVouchers\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\ReceiptVouchers\ReceiptVoucherResource;
use App\Models\ReceiptVoucher;
use App\Services\ReceiptVoucherService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditReceiptVoucher extends EditRecord
{
    protected static string $resource = ReceiptVoucherResource::class;

    protected static ?string $title = 'تعديل سند قبض عميل';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['allocations'] = $this->getRecord()
            ->allocations()
            ->get(['sales_invoice_id', 'amount'])
            ->map(fn ($allocation): array => [
                'sales_invoice_id' => $allocation->sales_invoice_id,
                'amount' => $allocation->amount,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(ReceiptVoucherService::class)->update($record, $data);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('حفظ التعديلات');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تعديل سند قبض العميل بنجاح.';
    }

    protected function getHeaderActions(): array
    {
        return [
            ProtectedDeleteAction::make()
                ->modalHeading('حذف سند قبض العميل')
                ->successNotificationTitle('تم حذف سند قبض العميل بنجاح.')
                ->using(fn (ReceiptVoucher $record): bool => app(ReceiptVoucherService::class)->delete($record)),
        ];
    }
}
