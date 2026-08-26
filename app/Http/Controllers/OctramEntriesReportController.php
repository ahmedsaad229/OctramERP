<?php

namespace App\Http\Controllers;

use App\Models\OctramEntry;
use Illuminate\View\View;

class OctramEntriesReportController extends Controller
{
    public function __invoke(): View
    {
        $entries = OctramEntry::query()
            ->with([
                'supplier',
                'customer',
                'purchaseItem',
                'salesItem',
            ])
            ->orderBy('purchase_date')
            ->orderBy('id')
            ->get();

        return view('print.octram-entries-report', [
            'entries' => $entries,
            'printedAt' => now(),
        ]);
    }
}