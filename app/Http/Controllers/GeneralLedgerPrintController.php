<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\GeneralLedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class GeneralLedgerPrintController extends Controller
{
    public function __invoke(Request $request, GeneralLedgerService $service): View
    {
        $data = $request->validate([
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $report = $service->report(
            (int) $data['account_id'],
            $data['from_date'] ?? null,
            $data['to_date'] ?? null,
        );

        return view('print.general-ledger', [
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
            'report' => $report,
        ]);
    }
}
