<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\SalesQuotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SalesQuotationsExportController extends Controller
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

        $query = SalesQuotation::query()
            ->with([
                'customer',
                'warehouse',
                'salesResponsible',
                'items.item',
                'items.unit',
            ])
            ->withCount('items');

        /*
         * إذا وصل ids من جدول Filament:
         * التقرير يعرض نفس السجلات الظاهرة بعد الفلترة بالضبط.
         */
        if ($request->has('ids')) {
            if ($ids->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $ids->all());
            }
        }

        return $query
            ->orderByDesc('quotation_date')
            ->orderByDesc('id');
    }

    private function quotations(Request $request)
    {
        return $this->query($request)->get();
    }

    public function excel(Request $request): Response
    {
        $quotations = $this->quotations($request);

        $html = view(
            'exports.sales-quotations-excel',
            compact('quotations')
        )->render();

        $fileName =
            'sales-quotations-' .
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
        return view('print.sales-quotations-report', [
            'quotations' => $this->quotations($request),
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
        ]);
    }

    public function detailed(Request $request): View
    {
        return view(
            'print.sales-quotations-detailed-report',
            [
                'quotations' => $this->quotations($request),
                'settings' => CompanySetting::current(),
                'printedAt' => now(),
            ]
        );
    }
}
