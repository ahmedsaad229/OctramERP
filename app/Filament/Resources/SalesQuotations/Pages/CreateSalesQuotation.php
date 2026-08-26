<?php

namespace App\Filament\Resources\SalesQuotations\Pages;

use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use App\Models\Item;
use App\Services\SalesQuotationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSalesQuotation extends CreateRecord
{
    protected static string $resource = SalesQuotationResource::class;

    protected static bool $canCreateAnother = false;


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
                'item_code_state' => $item->code,
                'is_stock_item_state' => $item->is_stock_item,
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name,
                'quantity' => 1,
                'unit_price' => (float) ($item->sale_price ?? 0),
                'discount_type' => 'value',
                'discount_value' => 0,
                'discount_amount' => 0,
                'tax_exempt' => false,
                'tax_amount' => 0,
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
        return app(SalesQuotationService::class)->create($data);
    }
}
