<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\Item;
use App\Services\PurchaseRequestService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreatePurchaseRequest extends CreateRecord
{
    protected static string $resource = PurchaseRequestResource::class;


    public function mount(): void
    {
        parent::mount();

        $ids = $this->selectedItemIds();

        if ($ids === []) {
            return;
        }

        $items = Item::query()
            ->with('unit:id,name')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Item $item): int => array_search((int) $item->getKey(), $ids, true))
            ->map(fn (Item $item): array => [
                'item_id' => $item->getKey(),
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name,
                'requested_quantity' => 1,
                'notes' => null,
            ])
            ->values()
            ->all();

        if ($items === []) {
            return;
        }

        $state = $this->form->getRawState();
        $state['items'] = $items;
        $this->form->fill($state);
    }

    /** @return array<int, int> */
    private function selectedItemIds(): array
    {
        return collect(explode(',', (string) request()->query('item_ids', '')))
            ->map(fn (string $id): int => (int) trim($id))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->take(100)
            ->values()
            ->all();
    }

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
