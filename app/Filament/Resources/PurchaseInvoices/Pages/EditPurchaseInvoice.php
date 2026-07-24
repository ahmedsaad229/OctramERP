<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;

use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Services\Inventory\PurchaseInvoiceService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseInvoice extends EditRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
    protected function afterSave(): void { app(PurchaseInvoiceService::class)->post($this->record); }
}
