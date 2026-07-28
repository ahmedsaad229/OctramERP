<?php

namespace App\Http\Controllers;

use App\Filament\Pages\CustomerPurchaseOrderMonitoring;
use App\Models\CustomerPurchaseOrder;
use App\Services\CustomerPurchaseOrderMonitoringService;
use App\Services\ReportCsvExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerPurchaseOrderMonitoringReportController extends Controller
{
    public function print(Request $request, CustomerPurchaseOrderMonitoringService $service): View
    {
        abort_unless(CustomerPurchaseOrderMonitoring::canAccess(), 403);
        $validated = $this->validated($request);

        return view('print.customer-purchase-order-monitoring', [
            'report' => $service->report($validated, $validated['view'] ?? 'all'),
            'printedAt' => now(),
        ]);
    }

    public function excel(
        Request $request,
        CustomerPurchaseOrderMonitoringService $service,
        ReportCsvExporter $exporter,
    ): StreamedResponse {
        abort_unless(CustomerPurchaseOrderMonitoring::canAccess(), 403);
        $validated = $this->validated($request);
        $report = $service->report($validated, $validated['view'] ?? 'all');

        $rows = [
            ['رقم المستند', 'رقم أمر العميل', 'العميل', 'المشروع', 'تاريخ الأمر', 'تاريخ التسليم المطلوب', 'الحالة', 'نسبة التنفيذ', 'عدد الأصناف', 'المكتملة', 'المتبقية', 'الكمية المتبقية', 'الفواتير', 'المرفقات', 'متأخر', 'الأيام'],
            ...$report['rows']->map(fn (array $row): array => [
                $row['documentNumber'], $row['customerOrderNumber'], $row['customer'], $row['project'],
                $row['orderDate'], $row['deliveryDate'], $row['statusLabel'], $row['percentage'],
                $row['itemsCount'], $row['completedItems'], $row['remainingItems'], $row['remainingQuantity'],
                $row['invoiceCount'], $row['attachmentCount'], $row['delayed'] ? 'متأخر' : '',
                $row['days'],
            ])->all(),
        ];

        return $exporter->download('customer-purchase-order-monitoring-'.now()->format('Ymd-His').'.csv', $rows);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(CustomerPurchaseOrder::statusOptions()))],
            'order_from' => ['nullable', 'date'],
            'order_to' => ['nullable', 'date', 'after_or_equal:order_from'],
            'delivery_from' => ['nullable', 'date'],
            'delivery_to' => ['nullable', 'date', 'after_or_equal:delivery_from'],
            'project' => ['nullable', 'string', 'max:255'],
            'delayed_only' => ['nullable', 'boolean'],
            'remaining_only' => ['nullable', 'boolean'],
            'attachments_only' => ['nullable', 'boolean'],
            'due_soon' => ['nullable', 'boolean'],
            'view' => ['nullable', 'in:all,delayed,due_soon,partial,new,completed,suspended'],
        ]);
    }
}
