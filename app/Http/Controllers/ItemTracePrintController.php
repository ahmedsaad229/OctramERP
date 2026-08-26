<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Item;
use App\Services\ItemTraceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ItemTracePrintController extends Controller
{
    public function __invoke(Request $request, ItemTraceService $service): View
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (
            ! (bool) ($user->is_admin ?? false)
            && method_exists($user, 'hasPermission')
            && ! $user->hasPermission('items.view')
        ) {
            abort(403);
        }

        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'document_type' => ['nullable', 'string'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $documentType = (string) ($data['document_type'] ?? ItemTraceService::ALL);
        $options = $service->documentTypeOptions();

        if (! array_key_exists($documentType, $options)) {
            throw new HttpException(422, 'مكان البحث المحدد غير صالح.');
        }

        $item = Item::query()
            ->with(['category', 'unit'])
            ->findOrFail((int) $data['item_id']);

        $report = $service->search(
            $item->getKey(),
            $documentType,
            $data['from_date'] ?? null,
            $data['to_date'] ?? null,
        );

        return view('print.item-trace', [
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
            'item' => $item,
            'report' => $report,
            'documentType' => $documentType,
            'documentTypeLabel' => $options[$documentType] ?? 'الكل',
            'fromDate' => $data['from_date'] ?? null,
            'toDate' => $data['to_date'] ?? null,
        ]);
    }
}
