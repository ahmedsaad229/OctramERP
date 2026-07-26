<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Services\PurchaseRequestService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreatePurchaseRequest extends CreateRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(PurchaseRequestService::class)->create($data);
        } catch (ValidationException $exception) {
            $this->sendValidationFailureNotification($exception);

            throw $exception;
        }
    }

    protected function onValidationError(ValidationException $exception): void
    {
        $this->sendValidationFailureNotification($exception);
    }

    private function sendValidationFailureNotification(ValidationException $exception): void
    {
        $hasMissingUnit = collect($exception->errors())
            ->keys()
            ->contains(fn (string $field): bool => str_ends_with($field, '.unit_id'));

        Notification::make()
            ->danger()
            ->title(
                $hasMissingUnit
                    ? 'تعذر حفظ طلب الشراء'
                    : 'تعذر حفظ طلب الشراء. راجع البيانات المطلوبة باللون الأحمر.',
            )
            ->when(
                $hasMissingUnit,
                fn (Notification $notification): Notification => $notification->body(
                    'يوجد صنف بدون وحدة افتراضية. راجع الأصناف المطلوبة.',
                ),
            )
            ->send();
    }
}
