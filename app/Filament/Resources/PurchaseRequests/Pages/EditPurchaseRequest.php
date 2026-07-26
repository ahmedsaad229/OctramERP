<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = [
            ...$data,
            ...PurchaseRequestForm::departmentFormState($data['department'] ?? null),
            'requester_name' => $this->getRecord()->requestedBy?->name,
        ];
        $data['items'] = $this->getRecord()
            ->items()
            ->with('unit:id,name')
            ->get()
            ->map(fn ($item): array => [
                ...$item->only(['item_id', 'unit_id', 'requested_quantity', 'notes']),
                'unit_name' => $item->unit?->name,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(PurchaseRequestService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ProtectedDeleteAction::make()
                ->using(fn (PurchaseRequest $record): bool => app(PurchaseRequestService::class)->delete($record)),
        ];
    }
}
