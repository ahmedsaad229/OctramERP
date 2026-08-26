<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\DueObligationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DueObligationsPrintController extends Controller
{
    public function __invoke(
        Request $request,
        DueObligationService $service
    ): View {
        $filters = $request->validate([
            'source_type' => [
                'nullable',
                'in:sale,purchase',
            ],
            'payment_type' => [
                'nullable',
                'string',
            ],
            'warehouse_id' => [
                'nullable',
                'integer',
            ],
            'party' => [
                'nullable',
                'string',
                'max:100',
            ],
            'date_from' => [
                'nullable',
                'date',
            ],
            'date_until' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
            'due_status' => [
                'nullable',
                'in:cash,future,today,overdue',
            ],
            'overdue' => [
                'nullable',
                'boolean',
            ],
        ]);

        $report = $service->report($filters);

        return view('print.due-obligations', [
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
            'records' => $report['records'],
            'totals' => $report['totals'],
            'filters' => $filters,
        ]);
    }
}
