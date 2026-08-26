<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;



use Filament\Actions\Action;
use App\Models\PurchaseInvoice;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseInvoice extends ViewRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('طباعة / حفظ PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(
                    fn (PurchaseInvoice $record): string => route(
                        'purchase-invoices.print',
                        $record
                    )
                )
                ->openUrlInNewTab(),
        ];
    }
}
