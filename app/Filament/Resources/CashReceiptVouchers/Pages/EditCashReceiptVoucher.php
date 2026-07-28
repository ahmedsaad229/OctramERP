<?php

namespace App\Filament\Resources\CashReceiptVouchers\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\CashReceiptVouchers\CashReceiptVoucherResource;
use App\Models\ReceiptVoucher;
use App\Services\ReceiptVoucherService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCashReceiptVoucher extends EditRecord
{
    protected static string $resource = CashReceiptVoucherResource::class;

    protected static ?string $title = 'تعديل سند استلام نقدية';

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(ReceiptVoucherService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')->label('طباعة')->icon('heroicon-o-printer')
                ->url(fn (ReceiptVoucher $record): string => route('cash-receipt-vouchers.print', $record))
                ->openUrlInNewTab(),
            ProtectedDeleteAction::make()
                ->using(fn (ReceiptVoucher $record): bool => app(ReceiptVoucherService::class)->delete($record)),
        ];
    }
}
