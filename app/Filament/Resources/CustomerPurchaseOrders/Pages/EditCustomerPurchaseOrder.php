<?php

namespace App\Filament\Resources\CustomerPurchaseOrders\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\CustomerPurchaseOrders\CustomerPurchaseOrderResource;
use App\Models\CustomerPurchaseOrder;
use App\Services\CustomerPurchaseOrderService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCustomerPurchaseOrder extends EditRecord
{
    protected static string $resource = CustomerPurchaseOrderResource::class;

    protected static ?string $title = 'تعديل أمر توريد عميل';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord()->load(['items.unit', 'followUps', 'attachments']);
        $data['items'] = $record->items->map(fn ($item): array => [
            ...$item->only(['id', 'item_id', 'unit_id', 'ordered_quantity', 'executed_quantity', 'remaining_quantity', 'unit_price', 'description', 'notes']),
            'unit_name' => $item->unit?->name,
        ])->all();
        $data['followUps'] = $record->followUps->map(fn ($row): array => $row->only(['follow_up_date', 'event_type', 'note']))->all();
        $data['attachments'] = $record->attachments->map(fn ($row): array => $row->only(['file_path', 'original_name']))->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(CustomerPurchaseOrderService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')->label('طباعة')->icon('heroicon-o-printer')->url(fn (CustomerPurchaseOrder $record) => route('customer-purchase-orders.print', $record))->openUrlInNewTab(),
            ProtectedDeleteAction::make()->using(fn (CustomerPurchaseOrder $record) => app(CustomerPurchaseOrderService::class)->delete($record)),
        ];
    }
}
