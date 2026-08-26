<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\FiscalYear;
use App\Services\FiscalYearClosingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FiscalYearClosingPrintController extends Controller
{
    public function __invoke(Request $request, FiscalYearClosingService $service): View
    {
        $user = auth()->user();

        abort_unless(
            $user && (
                (bool) ($user->is_admin ?? false)
                || $user->hasPermission('journal_entries.view')
            ),
            403
        );

        $data = $request->validate([
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'retained_earnings_account_id' => ['required', 'integer', 'exists:accounts,id'],
        ]);

        $year = FiscalYear::query()->findOrFail((int) $data['fiscal_year_id']);

        return view('print.fiscal-year-closing', [
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
            'preview' => $service->preview(
                $year,
                (int) $data['retained_earnings_account_id'],
            ),
        ]);
    }
}
