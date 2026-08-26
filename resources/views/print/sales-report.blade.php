<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>تقرير المبيعات</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        :root {
            --primary: #123b67;
            --primary-dark: #0d2f54;
            --primary-soft: #eef5fc;
            --border: #bfd0e2;
            --text: #172033;
            --muted: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f7;
            color: var(--text);
            font-family: Arial, Tahoma, sans-serif;
            line-height: 1.5;
        }

        .report-page {
            width: min(297mm, calc(100% - 30px));
            min-height: 194mm;
            margin: 20px auto;
            padding: 8mm;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(15, 23, 42, .12);
            font-size: 10px;
        }

        .toolbar {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 12px;
        }

        .print-button {
            border: 0;
            border-radius: 7px;
            padding: 9px 18px;
            background: #2563eb;
            color: #fff;
            font-family: inherit;
            font-weight: 800;
            cursor: pointer;
        }

        .company-document-header {
            margin-bottom: 15px !important;
            padding: 14px 16px !important;
            border: 1px solid var(--border) !important;
            border-top: 5px solid var(--primary) !important;
            border-radius: 10px !important;
            background: #fff !important;
        }

        .company-document-layout {
            display: grid !important;
            grid-template-columns:
                minmax(190px, .8fr)
                minmax(310px, 1.5fr)
                minmax(140px, .65fr) !important;
            align-items: center !important;
            gap: 18px !important;
            direction: rtl !important;
        }

        .company-document-title {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 46px !important;
            margin-bottom: 8px !important;
            padding: 8px 14px !important;
            border-radius: 8px !important;
            background: var(--primary) !important;
            color: #fff !important;
            font-size: 20px !important;
            font-weight: 800 !important;
            text-align: center !important;
        }

        .company-document-company {
            text-align: center !important;
        }

        .company-document-company-name {
            color: var(--primary) !important;
            font-size: 19px !important;
            font-weight: 800 !important;
            white-space: nowrap !important;
        }

        .company-document-logo-wrap {
            display: flex !important;
            justify-content: flex-start !important;
            align-items: center !important;
            width: 100% !important;
            direction: ltr !important;
        }

        .company-document-logo {
            max-width: 140px !important;
            max-height: 82px !important;
            object-fit: contain !important;
        }

        .filter-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 20px;
            margin: 12px 0;
            padding: 10px 13px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #f8fbfe;
        }

        .filter-item span {
            color: var(--muted);
        }

        .filter-item strong {
            color: var(--primary-dark);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
            margin: 12px 0 14px;
        }

        .kpi-card {
            min-height: 70px;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: linear-gradient(180deg, #fff, #f8fbfe);
            text-align: center;
        }

        .kpi-label {
            color: var(--muted);
            font-size: 9.5px;
        }

        .kpi-value {
            margin-top: 7px;
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 900;
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        thead {
            display: table-header-group;
        }

        th,
        td {
            padding: 6px 4px;
            border-left: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            text-align: center;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }

        th:last-child,
        td:last-child {
            border-left: 0;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        th {
            background: var(--primary);
            color: #fff;
            font-weight: 800;
        }

        tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .text-right {
            text-align: right;
        }

        .numeric {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .total-row td {
            background: #f0f6fc;
            color: var(--primary-dark);
            font-weight: 900;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 55px;
            margin-top: 38px;
            padding: 0 30px;
            text-align: center;
        }

        .signature {
            min-height: 55px;
            padding-top: 9px;
            border-top: 1px solid #4b6075;
            color: var(--primary-dark);
            font-weight: 700;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 22px;
            padding-top: 8px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 9px;
        }

        @media print {
            body {
                background: #fff;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .report-page {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .toolbar {
                display: none !important;
            }
        }
    </style>
</head>

<body>
<main class="report-page">
    <div class="toolbar">
        <button
            type="button"
            class="print-button"
            onclick="window.print()"
        >
            طباعة / حفظ PDF
        </button>
    </div>

    <x-company-document-header
        :settings="$settings"
        document-title="تقرير المبيعات"
        document-number="SAL-REPORT"
        :document-date="$printedAt->format('d/m/Y')"
    />

    <section class="filter-summary">
        <div class="filter-item">
            <span>الفترة من:</span>
            <strong>{{ $filters['date_from'] ?? 'البداية' }}</strong>
        </div>

        <div class="filter-item">
            <span>إلى:</span>
            <strong>{{ $filters['date_until'] ?? 'اليوم' }}</strong>
        </div>

        @if (filled($filters['document_number'] ?? null))
            <div class="filter-item">
                <span>رقم الفاتورة:</span>
                <strong>{{ $filters['document_number'] }}</strong>
            </div>
        @endif
    </section>

    <section class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">عدد الفواتير</div>
            <div class="kpi-value">
                {{ number_format($totals['invoices_count']) }}
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">إجمالي قبل الضريبة</div>
            <div class="kpi-value">
                {{ number_format($totals['subtotal'], 2) }} ج.م
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">إجمالي الخصم</div>
            <div class="kpi-value">
                {{ number_format($totals['discount'], 2) }} ج.م
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">إجمالي الضريبة</div>
            <div class="kpi-value">
                {{ number_format($totals['tax'], 2) }} ج.م
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">صافي المبيعات</div>
            <div class="kpi-value">
                {{ number_format($totals['total'], 2) }} ج.م
            </div>
        </div>
    </section>

    <table aria-label="تفاصيل تقرير المبيعات">
        <colgroup>
            <col style="width: 4%">
            <col style="width: 9%">
            <col style="width: 11%">
            <col style="width: 18%">
            <col style="width: 13%">
            <col style="width: 9%">
            <col style="width: 10%">
            <col style="width: 8%">
            <col style="width: 8%">
            <col style="width: 10%">
        </colgroup>

        <thead>
        <tr>
            <th>م</th>
            <th>التاريخ</th>
            <th>رقم الفاتورة</th>
            <th>العميل</th>
            <th>المخزن</th>
            <th>نوع التعامل</th>
            <th>قبل الضريبة</th>
            <th>الخصم</th>
            <th>الضريبة</th>
            <th>الإجمالي</th>
        </tr>
        </thead>

        <tbody>
        @forelse ($records as $invoice)
            @php
                $paymentType = $invoice->payment_type;

                $paymentLabel = is_object($paymentType)
                    && method_exists($paymentType, 'label')
                        ? $paymentType->label()
                        : (string) $paymentType;
            @endphp

            <tr>
                <td class="numeric">{{ $loop->iteration }}</td>

                <td class="numeric">
                    {{ $invoice->invoice_date?->format('d/m/Y') }}
                </td>

                <td class="numeric">
                    {{ $invoice->document_number }}
                </td>

                <td class="text-right">
                    {{ $invoice->customer?->name ?: '—' }}
                </td>

                <td>
                    {{ $invoice->warehouse?->name ?: '—' }}
                </td>

                <td>{{ $paymentLabel }}</td>

                <td class="numeric">
                    {{ number_format((float) $invoice->items_subtotal, 2) }}
                </td>

                <td class="numeric">
                    {{ number_format((float) $invoice->discount_amount, 2) }}
                </td>

                <td class="numeric">
                    {{ number_format((float) $invoice->tax_amount, 2) }}
                </td>

                <td class="numeric">
                    <strong>
                        {{ number_format($invoice->totalAmount(), 2) }}
                    </strong>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10">لا توجد بيانات مطابقة للفلاتر المحددة.</td>
            </tr>
        @endforelse

        @if ($records->isNotEmpty())
            <tr class="total-row">
                <td colspan="6">إجمالي التقرير</td>

                <td class="numeric">
                    {{ number_format($totals['subtotal'], 2) }}
                </td>

                <td class="numeric">
                    {{ number_format($totals['discount'], 2) }}
                </td>

                <td class="numeric">
                    {{ number_format($totals['tax'], 2) }}
                </td>

                <td class="numeric">
                    {{ number_format($totals['total'], 2) }}
                </td>
            </tr>
        @endif
        </tbody>
    </table>

    <section class="signatures">
        <div class="signature">إعداد التقرير</div>
        <div class="signature">المراجعة</div>
        <div class="signature">الاعتماد</div>
    </section>

    <footer class="footer">
        <span>{{ $settings->commercialName() }}</span>

        <span>
            تاريخ الطباعة:
            {{ $printedAt->format('d/m/Y h:i A') }}
        </span>

        <span>
            عدد الفواتير:
            {{ number_format($totals['invoices_count']) }}
        </span>
    </footer>
</main>
</body>
</html>
