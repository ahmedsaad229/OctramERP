<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\BalanceSheetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BalanceSheetPrintController extends Controller
{
    public function __invoke(Request $request, BalanceSheetService $service): View
    {
        abort_unless(auth()->user()?->hasPermission('balance_sheet.print'), 403);

        $data = $request->validate([
            'as_of_date' => ['nullable', 'date'],
            'details' => ['nullable', 'boolean'],
        ]);

        return view('print.balance-sheet', [
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
            'report' => $service->report(
                $data['as_of_date'] ?? now()->toDateString(),
                filter_var($data['details'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ),
        ]);
    }
}
