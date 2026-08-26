<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>تقرير الاستحقاقات</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        :root {
            --primary: #123b67;
            --primary-dark: #0d2f54;
            --primary-soft: #edf5fc;
            --border: #bfd0e2;
            --text: #172033;
            --muted: #64748b;
            --danger: #b91c1c;
            --warning: #b45309;
            --success: #047857;
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
            box-shadow: 0 8px 28px rgb(15 23 42 / 12%);
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
            cursor: pointer;
            font: inherit;
            font-weight: 800;
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

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 9px;
            margin: 13px 0;
        }

        .summary-card {
            padding: 11px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: linear-gradient(180deg, #fff, #f8fbfe);
            text-align: center;
        }

        .summary-label {
            color: var(--muted);
            font-size: 9.5px;
        }

        .summary-value {
            margin-top: 6px;
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
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
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

        .numeric,
        .date,
        .reference {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .text-right {
            text-align: right;
        }

        .overdue-row {
            background: #fff1f2;
        }

        .today-row {
            background: #fff7ed;
        }

        .cash-row {
            background: #f8fafc;
        }

        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-overdue {
            background: #fee2e2;
            color: var(--danger);
        }

        .status-today {
            background: #ffedd5;
            color: var(--warning);
        }

        .status-future {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-cash {
            background: #e2e8f0;
            color: #475569;
        }

        .total-row td {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 900;
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
        document-title="تقرير الاستحقاقات"
        document-number="DUE-REPORT"
        :document-date="$printedAt->format('d/m/Y')"
    />

    <section class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">عدد المستندات</div>
            <div class="summary-value">
                {{ number_format($totals['documents_count']) }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">إجمالي المستندات</div>
            <div class="summary-value">
                {{ number_format($totals['total_amount'], 2) }} ج.م
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">إجمالي المسدد</div>
            <div class="summary-value">
                {{ number_format($totals['paid_amount'], 2) }} ج.م
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">إجمالي المتبقي</div>
            <div class="summary-value">
                {{ number_format($totals['remaining_amount'], 2) }} ج.م
            </div>
        </div>
    </section>

    @php
        $firstValue = static function ($record, array $fields, mixed $default = null) {
            foreach ($fields as $field) {
                $value = data_get($record, $field);

                if ($value !== null && $value !== '') {
                    return $value;
                }
            }

            return $default;
        };

        $formatDate = static function ($value): string {
            if (blank($value)) {
                return '—';
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('d/m/Y');
            }

            try {
                return \Carbon\Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };
    @endphp

    <table aria-label="تقرير الاستحقاقات">
        <colgroup>
            <col style="width: 4%">
            <col style="width: 7%">
            <col style="width: 11%">
            <col style="width: 16%">
            <col style="width: 9%">
            <col style="width: 9%">
            <col style="width: 9%">
            <col style="width: 10%">
            <col style="width: 10%">
            <col style="width: 10%">
            <col style="width: 9%">
        </colgroup>

        <thead>
        <tr>
            <th>م</th>
            <th>النوع</th>
            <th>رقم المستند</th>
            <th>العميل / المورد</th>
            <th>تاريخ المستند</th>
            <th>تاريخ الاستحقاق</th>
            <th>نوع التعامل</th>
            <th>قيمة المستند</th>
            <th>المسدد</th>
            <th>المتبقي</th>
            <th>الحالة</th>
        </tr>
        </thead>

        <tbody>
        @forelse ($records as $record)
            @php
                $sourceType = (string) $firstValue(
                    $record,
                    ['source_type', 'type'],
                    ''
                );

                $typeLabel = $sourceType === \App\Models\DueObligation::TYPE_SALE
                    ? 'بيع'
                    : ($sourceType === \App\Models\DueObligation::TYPE_PURCHASE
                        ? 'شراء'
                        : $sourceType);

                $paymentType = $firstValue(
                    $record,
                    ['payment_type'],
                    '—'
                );

                $paymentValue = is_object($paymentType) && isset($paymentType->value)
                    ? $paymentType->value
                    : (string) $paymentType;

                $paymentLabel = is_object($paymentType)
                    && method_exists($paymentType, 'label')
                        ? $paymentType->label()
                        : match ($paymentValue) {
                            'cash' => 'نقدي',
                            'credit' => 'آجل',
                            default => $paymentValue ?: '—',
                        };

                $dueDateValue = $firstValue($record, ['due_date']);
                $dueDate = filled($dueDateValue)
                    ? \Carbon\Carbon::parse($dueDateValue)
                    : null;

                $status = $paymentValue === 'cash' || ! $dueDate
                    ? \App\Models\DueObligation::STATUS_CASH
                    : ($dueDate->isToday()
                        ? \App\Models\DueObligation::STATUS_TODAY
                        : ($dueDate->isPast()
                            ? \App\Models\DueObligation::STATUS_OVERDUE
                            : \App\Models\DueObligation::STATUS_FUTURE));

                $statusLabel = match ($status) {
                    \App\Models\DueObligation::STATUS_OVERDUE => 'متأخر',
                    \App\Models\DueObligation::STATUS_TODAY => 'مستحق اليوم',
                    \App\Models\DueObligation::STATUS_FUTURE => 'مستحق لاحقًا',
                    default => 'كاش',
                };

                $rowClass = match ($status) {
                    \App\Models\DueObligation::STATUS_OVERDUE => 'overdue-row',
                    \App\Models\DueObligation::STATUS_TODAY => 'today-row',
                    \App\Models\DueObligation::STATUS_CASH => 'cash-row',
                    default => '',
                };

                $statusClass = match ($status) {
                    \App\Models\DueObligation::STATUS_OVERDUE => 'status-overdue',
                    \App\Models\DueObligation::STATUS_TODAY => 'status-today',
                    \App\Models\DueObligation::STATUS_FUTURE => 'status-future',
                    default => 'status-cash',
                };

                $total = (float) $firstValue(
                    $record,
                    ['total_amount', 'amount', 'document_total', 'invoice_total', 'total'],
                    0
                );

                $paid = (float) $firstValue(
                    $record,
                    ['paid_amount', 'amount_paid', 'paid'],
                    0
                );

                $remaining = (float) $firstValue(
                    $record,
                    ['remaining_amount', 'remaining', 'balance', 'due_amount'],
                    max(0, $total - $paid)
                );
            @endphp

            <tr class="{{ $rowClass }}">
                <td class="numeric">{{ $loop->iteration }}</td>

                <td>{{ $typeLabel ?: '—' }}</td>

                <td class="reference">
                    {{ $firstValue(
                        $record,
                        ['document_number', 'source_number', 'invoice_number', 'reference_number'],
                        '—'
                    ) }}
                </td>

                <td class="text-right">
                    {{ $firstValue(
                        $record,
                        ['party_name', 'customer_name', 'supplier_name', 'party.name'],
                        '—'
                    ) }}
                </td>

                <td class="date">
                    {{ $formatDate(
                        $firstValue(
                            $record,
                            ['document_date', 'invoice_date', 'date', 'source_date']
                        )
                    ) }}
                </td>

                <td class="date">
                    {{ $formatDate($dueDateValue) }}
                </td>

                <td>{{ $paymentLabel }}</td>

                <td class="numeric">
                    {{ number_format($total, 2) }}
                </td>

                <td class="numeric">
                    {{ number_format($paid, 2) }}
                </td>

                <td class="numeric">
                    <strong>{{ number_format($remaining, 2) }}</strong>
                </td>

                <td>
                    <span class="status {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11">لا توجد استحقاقات مسجلة.</td>
            </tr>
        @endforelse

        @if ($records->isNotEmpty())
            <tr class="total-row">
                <td colspan="7">إجمالي التقرير</td>

                <td class="numeric">
                    {{ number_format($totals['total_amount'], 2) }}
                </td>

                <td class="numeric">
                    {{ number_format($totals['paid_amount'], 2) }}
                </td>

                <td class="numeric">
                    {{ number_format($totals['remaining_amount'], 2) }}
                </td>

                <td>—</td>
            </tr>
        @endif
        </tbody>
    </table>

    <footer class="footer">
        <span>{{ $settings->commercialName() }}</span>

        <span>
            تاريخ الطباعة:
            {{ $printedAt->format('d/m/Y h:i A') }}
        </span>

        <span>
            عدد المستندات:
            {{ number_format($totals['documents_count']) }}
        </span>
    </footer>
</main>
</body>
</html>
