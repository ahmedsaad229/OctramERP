<?php

namespace App\Filament\Resources\OctramEntries\Pages;

use App\Filament\Resources\OctramEntries\OctramEntryResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOctramEntry extends EditRecord
{
    protected static string $resource = OctramEntryResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['invoice_total'] = round(
            collect($data['items'] ?? [])
                ->sum(fn (array $item): float =>
                    (float) ($item['price_before_tax'] ?? 0)
                    + (float) ($item['tax_amount'] ?? 0)
                ),
            2
        );

        $record->update($data);

        return $record;
    }

    protected function afterSave(): void
    {
        $this->record->update([
            'invoice_total' => round(
                (float) $this->record->items()->sum('price_including_tax'),
                2
            ),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('طباعة التقرير')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn (): string => route('octram.print', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}