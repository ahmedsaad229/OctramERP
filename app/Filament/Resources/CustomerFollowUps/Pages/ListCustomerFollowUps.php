<?php

namespace App\Filament\Resources\CustomerFollowUps\Pages;

use App\Filament\Resources\CustomerFollowUps\CustomerFollowUpResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerFollowUps extends ListRecords
{
    protected static string $resource = CustomerFollowUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('تسجيل متابعة جديدة')
                ->icon('heroicon-o-plus'),
        ];
    }
}
