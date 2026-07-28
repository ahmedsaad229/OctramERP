<?php

namespace App\Filament\Resources\CashPaymentVouchers\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\CashPaymentVouchers\CashPaymentVoucherResource;
use App\Models\SupplierPaymentVoucher;
use App\Services\SupplierPaymentVoucherService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCashPaymentVoucher extends EditRecord
{
    protected static string $resource = CashPaymentVoucherResource::class;

    protected static ?string $title = 'تعديل سند صرف نقدية';

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(SupplierPaymentVoucherService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')->label('طباعة')->icon('heroicon-o-printer')
                ->url(fn (SupplierPaymentVoucher $record): string => route('cash-payment-vouchers.print', $record))
                ->openUrlInNewTab(),
            ProtectedDeleteAction::make()
                ->using(fn (SupplierPaymentVoucher $record): bool => app(SupplierPaymentVoucherService::class)->delete($record)),
        ];
    }
}
