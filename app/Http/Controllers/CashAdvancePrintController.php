<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Models\CompanySetting;
use Illuminate\Contracts\View\View;

class CashAdvancePrintController extends Controller
{
    public function __invoke(CashAdvance $cashAdvance): View
    {
        abort_unless(
            auth()->user()?->hasPermission('cash_advances.print') === true,
            403
        );

        $cashAdvance->load('settlements');

        return view('print.cash-advance', [
            'advance' => $cashAdvance,
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
        ]);
    }
}
