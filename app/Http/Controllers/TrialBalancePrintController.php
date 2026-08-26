<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\TrialBalanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TrialBalancePrintController extends Controller
{
    public function __invoke(Request $request, TrialBalanceService $service): View
    {
        $data = $request->validate([
            'from_date' => ['nullable','date'], 'to_date' => ['nullable','date','after_or_equal:from_date'],
            'movements_only' => ['nullable','boolean'],
        ]);
        return view('print.trial-balance', [
            'settings' => CompanySetting::current(), 'printedAt' => now(),
            'report' => $service->report($data['from_date'] ?? null, $data['to_date'] ?? null, filter_var($data['movements_only'] ?? true, FILTER_VALIDATE_BOOLEAN)),
        ]);
    }
}
