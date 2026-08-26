<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProtectedDeleteAction::make(),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تعديل الحساب بنجاح.';
    }
}
