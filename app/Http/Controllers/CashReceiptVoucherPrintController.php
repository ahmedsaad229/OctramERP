<?php

namespace App\Http\Controllers;

use App\Filament\Resources\CashReceiptVouchers\CashReceiptVoucherResource;
use App\Models\CompanySetting;
use App\Models\ReceiptVoucher;
use Illuminate\Contracts\View\View;

class CashReceiptVoucherPrintController extends Controller
{
    public function __invoke(ReceiptVoucher $receiptVoucher): View
    {
        abort_unless(auth()->user()?->hasPermission('cash_receipt_vouchers.view'), 403);
        $receiptVoucher->load(['customer', 'treasury']);

        return view('print.cash-receipt-voucher', [
            'voucher' => $receiptVoucher,
            'settings' => CompanySetting::current(),
        ]);
    }
}
