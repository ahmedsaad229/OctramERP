<?php

namespace App\Http\Controllers;

use App\Enums\PaymentType;
use App\Models\CompanySetting;
use App\Services\Reports\SalesReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReportController extends Controller
{
    public function print(Request $request): View
    {
        $filters = $this->filters($request);
        $report = app(SalesReportService::class)->report($filters);

        return view('print.sales-report', [
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
            'filters' => $filters,
            'records' => $report['records'],
            'totals' => $report['totals'],
        ]);
    }

    public function excel(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $records = app(SalesReportService::class)->records($filters);

        $fileName = 'sales-report-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(
            function () use ($records): void {
                $output = fopen('php://output', 'wb');

                fwrite($output, "\xEF\xBB\xBF");

                fputcsv($output, [
                    'رقم الفاتورة',
                    'التاريخ',
                    'العميل',
                    'المخزن',
                    'نوع التعامل',
                    'قبل الضريبة',
                    'الخصم',
                    'الضريبة',
                    'الإجمالي النهائي',
                ]);

                foreach ($records as $invoice) {
                    $paymentType = $invoice->payment_type;

                    $paymentLabel = is_object($paymentType)
                        && method_exists($paymentType, 'label')
                            ? $paymentType->label()
                            : (PaymentType::tryFrom((string) $paymentType)?->label()
                                ?? (string) $paymentType);

                    fputcsv($output, [
                        $invoice->document_number,
                        $invoice->invoice_date?->format('d/m/Y'),
                        $invoice->customer?->name,
                        $invoice->warehouse?->name,
                        $paymentLabel,
                        number_format(
                            (float) ($invoice->items_subtotal ?? 0),
                            2,
                            '.',
                            ''
                        ),
                        number_format(
                            (float) ($invoice->discount_amount ?? 0),
                            2,
                            '.',
                            ''
                        ),
                        number_format(
                            (float) ($invoice->tax_amount ?? 0),
                            2,
                            '.',
                            ''
                        ),
                        number_format(
                            $invoice->totalAmount(),
                            2,
                            '.',
                            ''
                        ),
                    ]);
                }

                fclose($output);
            },
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_until' => ['nullable', 'date', 'after_or_equal:date_from'],
            'customer_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'payment_type' => ['nullable', 'string'],
            'document_number' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
