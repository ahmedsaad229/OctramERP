<?php

namespace App\Filament\Resources\CustomerFollowUps\Pages;

use App\Filament\Resources\CustomerFollowUps\CustomerFollowUpResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerFollowUp extends CreateRecord
{
    protected static string $resource = CustomerFollowUpResource::class;

    protected static ?string $title = 'تسجيل متابعة عميل';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم تسجيل متابعة العميل بنجاح.';
    }
}
