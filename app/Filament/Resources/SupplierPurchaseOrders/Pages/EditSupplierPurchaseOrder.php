<?php

namespace App\Filament\Resources\SupplierPurchaseOrders\Pages;


use Filament\Actions\Action;
use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\SupplierPurchaseOrders\SupplierPurchaseOrderResource;
use App\Models\SupplierPurchaseOrder;
use App\Services\SupplierPurchaseOrderService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSupplierPurchaseOrder extends EditRecord
{
    protected static string $resource = SupplierPurchaseOrderResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $service = app(SupplierPurchaseOrderService::class);
        $payload = $service->requestSelectionPayload(
            $this->getRecord()->purchase_request_id,
            $this->getRecord()->getKey(),
        );
        $remaining = collect($payload['items'])->keyBy('purchase_request_item_id');

        $data = [
            ...$data,
            ...collect($payload)->except(['items', 'purchase_request_id'])->all(),
        ];

        $data['items'] = $this->getRecord()->items->map(function ($item) use ($remaining): array {
            $row = $remaining->get($item->purchase_request_item_id, []);

            return [
                ...$row,
                'purchase_request_item_id' => $item->purchase_request_item_id,
                'item_id' => $item->item_id,
                'unit_id' => $item->unit_id,
                'ordered_quantity' => (float) $item->ordered_quantity,
                'unit_price' => (float) $item->unit_price,
                'notes' => $item->notes,
            ];
        })->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(SupplierPurchaseOrderService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
                        Action::make('print')
                ->label('طباعة / حفظ PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(
                    fn (SupplierPurchaseOrder $record): string => route(
                        'supplier-purchase-orders.print',
                        $record
                    )
                )
                ->openUrlInNewTab(),
ProtectedDeleteAction::make()
                ->using(fn (SupplierPurchaseOrder $record): bool => app(SupplierPurchaseOrderService::class)->delete($record)),
        ];
    }
}
