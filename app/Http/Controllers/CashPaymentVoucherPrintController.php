<?php

namespace App\Http\Controllers;

use App\Filament\Resources\CashPaymentVouchers\CashPaymentVoucherResource;
use App\Models\CompanySetting;
use App\Models\SupplierPaymentVoucher;
use Illuminate\Contracts\View\View;

class CashPaymentVoucherPrintController extends Controller
{
    public function __invoke(SupplierPaymentVoucher $supplierPaymentVoucher): View
    {
        abort_unless(CashPaymentVoucherResource::canEdit($supplierPaymentVoucher), 403);
        $supplierPaymentVoucher->load(['supplier', 'treasury']);

        return view('print.cash-payment-voucher', [
            'voucher' => $supplierPaymentVoucher,
            'settings' => CompanySetting::current(),
        ]);
    }
}
