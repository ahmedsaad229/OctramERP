<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\SupplierStatementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SupplierStatementPrintController extends Controller
{
    public function __invoke(Request $request, SupplierStatementService $service): View
    {
        $validated = $request->validate([
            'supplier' => ['required', 'integer', 'exists:suppliers,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'transaction_type' => ['nullable', 'string'],
        ]);

        abort_unless(
            blank($validated['transaction_type'] ?? null)
                || array_key_exists($validated['transaction_type'], $service->transactionTypeOptions()),
            422,
        );

        return view('print.supplier-statement', [
            'report' => $service->report(
                (int) $validated['supplier'],
                $validated['from_date'] ?? null,
                $validated['to_date'] ?? null,
                $validated['transaction_type'] ?? null,
            ),
            'settings' => CompanySetting::current(),
            'money' => fn (float $amount): string => $service->money($amount),
            'printedAt' => now(),
        ]);
    }
}
