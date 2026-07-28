<?php

namespace App\Http\Controllers;

use App\Filament\Pages\ItemMovement;
use App\Models\CompanySetting;
use App\Services\ItemMovementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ItemMovementPrintController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless(ItemMovement::canAccess(), 403);
        $validated = $request->validate([
            'item' => ['required', 'integer', 'exists:items,id'],
            'warehouse' => ['nullable', 'integer', 'exists:warehouses,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'transaction_type' => ['nullable', 'string'],
        ]);

        return view('print.item-movement', [
            'report' => app(ItemMovementService::class)->report(
                (int) $validated['item'],
                isset($validated['warehouse']) ? (int) $validated['warehouse'] : null,
                $validated['from_date'] ?? null,
                $validated['to_date'] ?? null,
                $validated['transaction_type'] ?? null,
            ),
            'settings' => CompanySetting::current(),
        ]);
    }
}
