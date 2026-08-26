<?php

namespace App\Filament\Resources\CashReceiptVouchers\Pages;

use App\Filament\Resources\CashReceiptVouchers\CashReceiptVoucherResource;
use App\Models\ReceiptVoucher;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCashReceiptVoucher extends ViewRecord
{
    protected static string $resource = CashReceiptVoucherResource::class;

    protected static ?string $title = 'عرض سند استلام نقدية';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('طباعة')
                ->icon('heroicon-o-printer')
                ->url(
                    fn (ReceiptVoucher $record): string =>
                        route('cash-receipt-vouchers.print', $record)
                )
                ->openUrlInNewTab(),

            EditAction::make()
                ->label('تعديل')
                ->visible(
                    fn (ReceiptVoucher $record): bool =>
                        CashReceiptVoucherResource::canEdit($record)
                ),
        ];
    }
}
