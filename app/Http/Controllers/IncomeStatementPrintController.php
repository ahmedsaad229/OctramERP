<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\IncomeStatementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class IncomeStatementPrintController extends Controller
{
    public function __invoke(Request $request, IncomeStatementService $service): View
    {
        abort_unless(auth()->user()?->hasPermission('income_statement.print'), 403);

        $data = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'details' => ['nullable', 'boolean'],
        ]);

        return view('print.income-statement', [
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
            'report' => $service->report(
                $data['from_date'] ?? null,
                $data['to_date'] ?? null,
                filter_var($data['details'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ),
        ]);
    }
}
