<?php

namespace App\Http\Controllers;

use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Models\CompanySetting;
use App\Models\SalesInvoice;
use Illuminate\Contracts\View\View;

class SalesInvoicePrintController extends Controller
{
    public function __invoke(SalesInvoice $salesInvoice): View
    {
        abort_unless(SalesInvoiceResource::canView($salesInvoice), 403);

        $salesInvoice->load([
            'customer',
            'customerPurchaseOrder',
            'warehouse',
            'items.item',
            'items.unit',
        ]);

        return view('print.sales-invoice', [
            'invoice' => $salesInvoice,
            'settings' => CompanySetting::current(),
        ]);
    }
}
