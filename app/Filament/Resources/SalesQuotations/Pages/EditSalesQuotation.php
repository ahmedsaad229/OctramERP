<?php

namespace App\Filament\Resources\SalesQuotations\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use App\Models\SalesQuotation;
use App\Services\SalesQuotationService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSalesQuotation extends EditRecord
{
    protected static string $resource = SalesQuotationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items()->with(['item', 'unit'])->get()->map(fn ($item): array => [
            'item_id' => $item->item_id, 'item_code_state' => $item->item?->code,
            'unit_id' => $item->unit_id, 'unit_name' => $item->unit?->name,
            'quantity' => $item->quantity, 'unit_price' => $item->unit_price,
            'discount_amount' => $item->discount_amount, 'notes' => $item->notes,
        ])->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(SalesQuotationService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [ProtectedDeleteAction::make()->using(fn (SalesQuotation $record): bool => app(SalesQuotationService::class)->delete($record))];
    }
}
