<?php

namespace App\Filament\Resources\CustomerPurchaseOrders\Pages;

use App\Filament\Resources\CustomerPurchaseOrders\CustomerPurchaseOrderResource;
use App\Models\CustomerPurchaseOrder;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerPurchaseOrder extends ViewRecord
{
    protected static string $resource = CustomerPurchaseOrderResource::class;

    protected static ?string $title = 'عرض أمر توريد العميل';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('طباعة')
                ->icon('heroicon-o-printer')
                ->url(
                    fn (CustomerPurchaseOrder $record): string => route(
                        'customer-purchase-orders.print',
                        $record
                    )
                )
                ->openUrlInNewTab(),

            EditAction::make()
                ->label('تعديل')
                ->visible(
                    fn (CustomerPurchaseOrder $record): bool =>
                        CustomerPurchaseOrderResource::canEdit($record)
                ),
        ];
    }
}
