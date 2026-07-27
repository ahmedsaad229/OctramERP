<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\CustomerStatementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CustomerStatementPrintController extends Controller
{
    public function __invoke(Request $request, CustomerStatementService $service): View
    {
        $validated = $request->validate([
            'customer' => ['required', 'integer', 'exists:customers,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'transaction_type' => ['nullable', 'string'],
        ]);

        abort_unless(
            blank($validated['transaction_type'] ?? null)
                || array_key_exists($validated['transaction_type'], $service->transactionTypeOptions()),
            422,
        );

        return view('print.customer-statement', [
            'report' => $service->report(
                (int) $validated['customer'],
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
