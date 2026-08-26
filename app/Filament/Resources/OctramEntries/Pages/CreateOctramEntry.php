<?php

namespace App\Filament\Resources\OctramEntries\Pages;

use App\Filament\Resources\OctramEntries\OctramEntryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOctramEntry extends CreateRecord
{
    protected static string $resource = OctramEntryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['invoice_total'] = round(
            collect($data['items'] ?? [])
                ->sum(fn (array $item): float =>
                    (float) ($item['price_before_tax'] ?? 0)
                    + (float) ($item['tax_amount'] ?? 0)
                ),
            2
        );

        return static::getModel()::create($data);
    }

    protected function afterCreate(): void
    {
        $this->record->update([
            'invoice_total' => round(
                (float) $this->record->items()->sum('price_including_tax'),
                2
            ),
        ]);
    }
}