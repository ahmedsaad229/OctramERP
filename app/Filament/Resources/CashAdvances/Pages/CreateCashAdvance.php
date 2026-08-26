<?php

namespace App\Filament\Resources\CashAdvances\Pages;

use App\Filament\Resources\CashAdvances\CashAdvanceResource;
use App\Models\CashAdvance;
use Filament\Resources\Pages\CreateRecord;

class CreateCashAdvance extends CreateRecord
{
    protected static string $resource = CashAdvanceResource::class;

    protected static bool $canCreateAnother = false;

    protected function afterCreate(): void
    {
        /** @var CashAdvance $advance */
        $advance = $this->record;

        $advance->recalculate();

        $this->record = $advance->fresh();
    }
}
