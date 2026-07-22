<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lastId = (Customer::max('id') ?? 0) + 1;

        $data['code'] = 'CUS-' . str_pad($lastId, 5, '0', STR_PAD_LEFT);

        return $data;
    }
}