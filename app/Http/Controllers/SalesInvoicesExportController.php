<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SalesInvoicesExportController extends Controller
{
    private function query(Request $request): Builder
    {
        $ids = collect(
            explode(',', (string) $request->query('ids', ''))
        )
            ->filter(fn ($id) => ctype_digit(trim($id)))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $query = SalesInvoice::query()
            ->with([
                'customer',
                'warehouse',
                'salesQuotation',
                'customerPurchaseOrder',
                'items.item',
                'items.unit',
            ])
            ->withCount('items');

        if ($request->has('ids')) {
            if ($ids->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $ids->all());
            }
        }

        return $query
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');
    }

    private function invoices(Request $request)
    {
        return $this->query($request)->get();
    }

    public function excel(Request $request): Response
    {
        $invoices = $this->invoices($request);

        $html = view(
            'exports.sales-invoices-excel',
            compact('invoices')
        )->render();

        $fileName =
            'sales-invoices-' .
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

    public function pdf(Request $request): View
    {
        return view('print.sales-invoices-report', [
            'invoices' => $this->invoices($request),
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
        ]);
    }

    public function detailed(Request $request): View
    {
        return view(
            'print.sales-invoices-detailed-report',
            [
                'invoices' => $this->invoices($request),
                'settings' => CompanySetting::current(),
                'printedAt' => now(),
            ]
        );
    }
}
