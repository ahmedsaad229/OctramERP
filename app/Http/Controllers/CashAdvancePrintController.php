<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Models\CompanySetting;
use Illuminate\Contracts\View\View;

class CashAdvancePrintController extends Controller
{
    public function __invoke(CashAdvance $cashAdvance): View
    {
        $cashAdvance->load('settlements');

        return view('print.cash-advance', [
            'advance' => $cashAdvance,
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
        ]);
    }
}
