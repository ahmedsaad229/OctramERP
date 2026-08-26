<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\CustomerFollowUp;
use Illuminate\Contracts\View\View;

class CustomerFollowUpPrintController extends Controller
{
    public function __invoke(CustomerFollowUp $customerFollowUp): View
    {
        $customerFollowUp->load([
            'customer',
            'salesResponsible',
            'creator',
        ]);

        return view('print.customer-follow-up', [
            'followUp' => $customerFollowUp,
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
        ]);
    }
}
