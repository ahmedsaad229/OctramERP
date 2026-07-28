<?php

namespace App\Http\Controllers;

use App\Filament\Pages\ItemMovement;
use App\Services\ItemMovementService;
use App\Services\ReportCsvExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemMovementExportController extends Controller
{
    public function __invoke(
        Request $request,
        ItemMovementService $service,
        ReportCsvExporter $exporter,
    ): StreamedResponse {
        abort_unless(ItemMovement::canAccess(), 403);
        $validated = $request->validate([
            'item' => ['required', 'integer', 'exists:items,id'],
            'warehouse' => ['nullable', 'integer', 'exists:warehouses,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'transaction_type' => ['nullable', 'string'],
        ]);

        $report = $service->report(
            (int) $validated['item'],
            isset($validated['warehouse']) ? (int) $validated['warehouse'] : null,
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
            $validated['transaction_type'] ?? null,
        );

        $rows = [
            ['الصنف', $report['item']->name],
            ['المخزن', $report['warehouse']?->name ?? 'كل المخازن'],
            ['الرصيد الافتتاحي', $report['openingQuantity']],
            [],
            ['التاريخ', 'نوع المستند', 'رقم المستند', 'المخزن', 'البيان', 'وارد', 'صادر', 'الرصيد', 'تكلفة الوحدة', 'قيمة الحركة', 'قيمة الرصيد', 'متوسط التكلفة'],
            ...$report['rows']->map(fn (array $row): array => [
                $row['date'], $row['typeLabel'], $row['reference'], $row['warehouse'], $row['description'],
                $row['inbound'], $row['outbound'], $row['runningQuantity'], $row['unitCost'],
                $row['movementValue'], $row['runningValue'], $row['runningAverage'],
            ])->all(),
            [],
            ['إجمالي الوارد', $report['totalInbound']],
            ['إجمالي الصادر', $report['totalOutbound']],
            ['الرصيد الختامي', $report['closingQuantity']],
        ];

        return $exporter->download('item-movement-'.now()->format('Ymd-His').'.csv', $rows);
    }
}
