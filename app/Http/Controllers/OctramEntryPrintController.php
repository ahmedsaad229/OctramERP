<?php

namespace App\Http\Controllers;

use App\Models\OctramEntry;
use Illuminate\View\View;

class OctramEntryPrintController extends Controller
{
    public function __invoke(OctramEntry $octramEntry): View
    {
        $octramEntry->load(['supplier', 'customer', 'purchaseItem', 'salesItem']);

        return view('print.octram-entry', [
            'entry' => $octramEntry,
            'printedAt' => now(),
        ]);
    }
}