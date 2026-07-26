<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;

use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Services\Inventory\PurchaseInvoiceService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreatePurchaseInvoice extends CreateRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(PurchaseInvoiceService::class)->create($data);
        } catch (ValidationException $exception) {
            $this->notifyValidationFailure();

            throw $exception;
        }
    }

    protected function onValidationError(ValidationException $exception): void
    {
        $this->notifyValidationFailure();
    }

    private function notifyValidationFailure(): void
    {
        Notification::make()
            ->danger()
            ->title('تعذر حفظ فاتورة الشراء')
            ->body('راجع بيانات أمر التوريد والكميات المطلوبة.')
            ->send();
    }
}
