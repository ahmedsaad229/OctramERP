<?php

namespace App\Http\Controllers;

use App\Services\CustomerStatementService;
use App\Services\ReportCsvExporter;
use App\Services\SupplierStatementService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartyStatementExportController extends Controller
{
    public function customer(Request $request, CustomerStatementService $service, ReportCsvExporter $exporter): StreamedResponse
    {
        $validated = $this->validateStatement($request, 'customer', 'customers', $service->transactionTypeOptions());
        $report = $service->report(
            (int) $validated['customer'],
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
            $validated['transaction_type'] ?? null,
        );

        $rows = [
            ['اسم العميل', $report['customer']->name],
            ['الفترة', $this->period($report['fromDate'], $report['toDate'])],
            ['رصيد أول المدة', $report['openingBalance']],
            [],
            ['التاريخ', 'نوع الحركة', 'رقم المستند', 'البيان', 'مدين', 'دائن', 'الرصيد بعد الحركة'],
            ...$report['rows']->map(fn (array $row): array => [
                $row['date'], $row['typeLabel'], $row['reference'], $row['description'],
                $row['debit'], $row['credit'], $row['runningBalance'],
            ])->all(),
            [],
            ['إجمالي المدين', $report['totalDebt']],
            ['إجمالي الدائن', $report['totalPaid']],
            ['الرصيد الختامي', $report['closingBalance']],
        ];

        return $exporter->download('customer-statement-'.now()->format('Ymd-His').'.csv', $rows);
    }

    public function supplier(Request $request, SupplierStatementService $service, ReportCsvExporter $exporter): StreamedResponse
    {
        $validated = $this->validateStatement($request, 'supplier', 'suppliers', $service->transactionTypeOptions());
        $report = $service->report(
            (int) $validated['supplier'],
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
            $validated['transaction_type'] ?? null,
        );

        $rows = [
            ['اسم المورد', $report['supplier']->name],
            ['الفترة', $this->period($report['fromDate'], $report['toDate'])],
            ['رصيد أول المدة', $report['openingBalance']],
            [],
            ['التاريخ', 'نوع الحركة', 'رقم المستند', 'البيان', 'مدين', 'دائن', 'الرصيد بعد الحركة'],
            ...$report['rows']->map(fn (array $row): array => [
                $row['date'], $row['typeLabel'], $row['reference'], $row['description'],
                $row['paid'], $row['purchases'], $row['runningBalance'],
            ])->all(),
            [],
            ['إجمالي المدين', $report['totalPaid']],
            ['إجمالي الدائن', $report['totalPurchases']],
            ['الرصيد الختامي', $report['closingBalance']],
        ];

        return $exporter->download('supplier-statement-'.now()->format('Ymd-His').'.csv', $rows);
    }

    /** @param array<string, string> $types
     * @return array<string, mixed>
     */
    private function validateStatement(Request $request, string $party, string $table, array $types): array
    {
        $validated = $request->validate([
            $party => ['required', 'integer', "exists:{$table},id"],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'transaction_type' => ['nullable', 'string'],
        ]);

        abort_unless(
            blank($validated['transaction_type'] ?? null)
                || array_key_exists($validated['transaction_type'], $types),
            422,
        );

        return $validated;
    }

    private function period(mixed $from, mixed $to): string
    {
        return ($from?->format('d/m/Y') ?? 'البداية').' — '.($to?->format('d/m/Y') ?? 'حتى اليوم');
    }
}
