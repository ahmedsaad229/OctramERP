<?php

namespace App\Filament\Resources\CashAdvances\Pages;

use App\Filament\Resources\CashAdvances\CashAdvanceResource;
use App\Models\CashAdvance;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditCashAdvance extends EditRecord
{
    protected static string $resource = CashAdvanceResource::class;

    protected function afterSave(): void
    {
        /** @var CashAdvance $advance */
        $advance = $this->record;

        $advance->recalculate();

        $this->record = $advance->fresh();

        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('طباعة العهدة')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(
                    fn (): string =>
                        route('cash-advances.print', $this->record)
                )
                ->openUrlInNewTab(),
        ];
    }
}
