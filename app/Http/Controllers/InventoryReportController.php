<?php

namespace App\Http\Controllers;

use App\Filament\Pages\InventoryMovementReport;
use App\Filament\Pages\InventoryStockBalanceReport;
use App\Filament\Pages\LowStockReport;
use App\Services\InventoryReportService;
use App\Services\ReportCsvExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReportController extends Controller
{
    public function print(Request $request, string $report): View
    {
        $this->authorizeReport($report);
        $filters = $this->validatedFilters($request, $report);

        return view('print.inventory-report', [
            'type' => $report,
            'report' => $this->report($report, $filters),
            'filters' => $filters,
            'title' => $this->title($report),
            'printedAt' => now(),
        ]);
    }

    public function excel(Request $request, string $report, ReportCsvExporter $exporter): StreamedResponse
    {
        $this->authorizeReport($report);
        $data = $this->report($report, $this->validatedFilters($request, $report));
        [$headings, $rows] = $this->exportRows($report, $data);

        return $exporter->download(
            "inventory-{$report}-".now()->format('Ymd-His').'.csv',
            [$headings, ...$rows],
        );
    }

    /** @return array<string, mixed> */
    private function validatedFilters(Request $request, string $report): array
    {
        $common = [
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
        ];

        $rules = match ($report) {
            'balances' => [
                ...$common,
                'has_balance' => ['nullable', 'boolean'],
            ],
            'movements' => [
                ...$common,
                'from_date' => ['nullable', 'date'],
                'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
                'transaction_type' => ['nullable', 'in:opening,purchase,sale,transfer_in,transfer_out,adjustment'],
                'reference_no' => ['nullable', 'string', 'max:255'],
            ],
            'low-stock' => [
                ...$common,
                'status' => ['nullable', 'in:out,low,normal'],
            ],
            default => abort(404),
        };

        return $request->validate($rules);
    }

    /** @return array<string, mixed> */
    private function report(string $report, array $filters): array
    {
        $service = app(InventoryReportService::class);

        return match ($report) {
            'balances' => $service->balances($filters),
            'movements' => $service->movements($filters),
            'low-stock' => $service->lowStock($filters),
            default => abort(404),
        };
    }

    private function authorizeReport(string $report): void
    {
        $allowed = match ($report) {
            'balances' => InventoryStockBalanceReport::canAccess(),
            'movements' => InventoryMovementReport::canAccess(),
            'low-stock' => LowStockReport::canAccess(),
            default => false,
        };

        abort_unless($allowed, 403);
    }

    private function title(string $report): string
    {
        return match ($report) {
            'balances' => 'تقرير أرصدة المخزون',
            'movements' => 'تقرير حركة المخزون',
            'low-stock' => 'الأصناف منخفضة الرصيد',
            default => abort(404),
        };
    }

    /** @return array{0: array<int, string>, 1: array<int, array<int, mixed>>} */
    private function exportRows(string $report, array $data): array
    {
        return match ($report) {
            'balances' => [
                ['كود الصنف', 'اسم الصنف', 'الفئة', 'المخزن', 'الوحدة', 'الكمية الحالية', 'متوسط التكلفة', 'قيمة المخزون'],
                $data['rows']->map(fn (array $row): array => [
                    $row['item_code'], $row['item_name'], $row['category'], $row['warehouse'], $row['unit'],
                    $row['quantity'], $row['average_cost'], $row['inventory_value'],
                ])->all(),
            ],
            'movements' => [
                ['التاريخ', 'نوع الحركة', 'رقم المستند', 'الصنف', 'المخزن', 'وارد', 'صادر', 'الرصيد بعد الحركة', 'التكلفة'],
                $data['rows']->map(fn (array $row): array => [
                    $row['date'], $row['type_label'], $row['reference'], $row['item'], $row['warehouse'],
                    $row['inbound'], $row['outbound'], $row['running_balance'], $row['unit_cost'],
                ])->all(),
            ],
            'low-stock' => [
                ['كود الصنف', 'الصنف', 'الفئة', 'المخزن', 'الرصيد الحالي', 'حد إعادة الطلب', 'الفرق', 'الحالة'],
                $data['rows']->map(fn (array $row): array => [
                    $row['item_code'], $row['item_name'], $row['category'], $row['warehouse'],
                    $row['quantity'], $row['reorder_level'], $row['difference'], $row['status_label'],
                ])->all(),
            ],
            default => abort(404),
        };
    }
}
