<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\PurchaseItemSalesTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PurchaseItemSalesTrackingExportController extends Controller
{
    private function report(Request $request): array
    {
        return app(PurchaseItemSalesTrackingService::class)->report(
            $request->only([
                'supplier_id',
                'item_id',
                'status',
                'from_date',
                'to_date',
            ])
        );
    }

    public function print(Request $request): View
    {
        return view('print.purchase-item-sales-tracking', [
            'report' => $this->report($request),
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
        ]);
    }

    public function excel(Request $request): Response
    {
        $report = $this->report($request);

        $html = view(
            'exports.purchase-item-sales-tracking',
            [
                'report' => $report,
            ]
        )->render();

        $fileName =
            'purchase-item-sales-tracking-' .
            now()->format('Y-m-d-His') .
            '.xls';

        return response("\xEF\xBB\xBF" . $html, 200, [
            'Content-Type' =>
                'application/vnd.ms-excel; charset=UTF-8',

            'Content-Disposition' =>
                'attachment; filename="' . $fileName . '"',

            'Cache-Control' => 'max-age=0',
        ]);
    }
}
