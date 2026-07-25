<?php

namespace App\Filament\Resources\ReceiptVouchers\Pages;

use App\Filament\Resources\ReceiptVouchers\ReceiptVoucherResource;
use App\Models\ReceiptVoucher;
use App\Services\ReceiptVoucherService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditReceiptVoucher extends EditRecord
{
    protected static string $resource = ReceiptVoucherResource::class;

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
        return 'تم تعديل سند القبض بنجاح.';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('هل تريد حذف سند القبض؟')
                ->successNotificationTitle('تم حذف سند القبض بنجاح.')
                ->using(fn (ReceiptVoucher $record): bool => app(ReceiptVoucherService::class)->delete($record)),
        ];
    }
}
