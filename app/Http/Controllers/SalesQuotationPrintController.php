<?php

namespace App\Http\Controllers;

use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use App\Models\CompanySetting;
use App\Models\SalesQuotation;
use Illuminate\Contracts\View\View;

class SalesQuotationPrintController extends Controller
{
    public function __invoke(SalesQuotation $salesQuotation): View
    {
        abort_unless(SalesQuotationResource::canView($salesQuotation), 403);

        $salesQuotation->load([
            'customer',
            'warehouse',
            'items.item',
            'items.unit',
        ]);

        return view('print.sales-quotation', [
            'quotation' => $salesQuotation,
            'settings' => CompanySetting::current(),
        ]);
    }
}
