<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تقرير تتبع الصنف - {{ $item->name }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        :root {
            --primary: #173a5e;
            --primary-dark: #102f4d;
            --primary-soft: #eaf2f9;
            --border: #cbd5e1;
            --text: #172033;
            --muted: #64748b;
            --success: #15803d;
            --danger: #b91c1c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef3f8;
            color: var(--text);
            font-family: Arial, Tahoma, sans-serif;
            font-size: 10px;
            line-height: 1.45;
        }

        .report-page {
            width: min(297mm, calc(100% - 32px));
            min-height: 194mm;
            margin: 24px auto;
            padding: 8mm;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 35px rgb(15 23 42 / 12%);
        }

        .toolbar {
            display: flex;
            justify-content: flex-start;
            gap: 8px;
            margin-bottom: 14px;
        }

        .print-button {
            border: 0;
            border-radius: 7px;
            padding: 9px 18px;
            background: #2563eb;
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            box-shadow: 0 3px 10px rgb(37 99 235 / 20%);
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

        .item-strip {
            display: grid;
            grid-template-columns: 1.4fr 110px 1fr 110px;
            gap: 8px;
            margin: 12px 0;
        }

        .item-box {
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--primary-soft);
            min-width: 0;
        }

        .item-box .label {
            color: var(--muted);
            font-size: 9px;
        }

        .item-box .value {
            margin-top: 3px;
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 9px;
            margin: 13px 0;
        }

        .summary-card {
            min-height: 72px;
            padding: 10px 11px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: linear-gradient(180deg, #fff, #f8fbfe);
            text-align: center;
        }

        .summary-card.highlight {
            border-color: #93c5fd;
            background: var(--primary-soft);
        }

        .summary-label {
            color: var(--muted);
            font-size: 9.5px;
        }

        .summary-value {
            margin-top: 5px;
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 900;
        }

        .period-bar {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 700;
        }

        .period-bar span {
            direction: rtl;
            unicode-bidi: isolate;
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
            white-space: nowrap;
        }

        tbody tr:nth-child(even) {
            background: #f8fbfe;
        }

        tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .text {
            text-align: right;
        }

        .date,
        .reference,
        .numeric {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .status {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 8px;
            font-weight: 800;
            white-space: nowrap;
        }

        .total-row td {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 900;
            border-bottom: 0;
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
                display: none;
            }
        }
    </style>

    @include('print.partials.report-style')
</head>

<body>
@php
    $money = static fn ($value): string => number_format((float) $value, 2);
    $qty = static fn ($value): string => number_format((float) $value, 2);
@endphp

<main class="report-page">
    <div class="toolbar">
        <button type="button" class="print-button" onclick="window.print()">
            طباعة / حفظ PDF
        </button>
    </div>

    <x-company-document-header
        class="company-document-header"
        :settings="$settings"
        document-title="تقرير تتبع الصنف"
        :document-number="$item->code"
        :document-date="$printedAt->format('d/m/Y')"
    />

    <section class="item-strip">
        <div class="item-box">
            <div class="label">اسم الصنف</div>
            <div class="value">{{ $item->name }}</div>
        </div>

        <div class="item-box">
            <div class="label">الكود</div>
            <div class="value reference">{{ $item->code }}</div>
        </div>

        <div class="item-box">
            <div class="label">الفئة</div>
            <div class="value">{{ $item->category?->name ?? '—' }}</div>
        </div>

        <div class="item-box">
            <div class="label">الوحدة</div>
            <div class="value">{{ $item->unit?->name ?? '—' }}</div>
        </div>
    </section>

    <section class="summary-grid">
        <div class="summary-card highlight">
            <div class="summary-label">مكان البحث</div>
            <div class="summary-value">{{ $documentTypeLabel }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">عدد الحركات</div>
            <div class="summary-value">{{ $report['count'] }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">إجمالي الكمية بالحركات</div>
            <div class="summary-value numeric">{{ $qty($report['total_quantity']) }}</div>
        </div>
    </section>

    <div class="period-bar">
        <span>
            من:
            {{ $fromDate
                ? \Carbon\Carbon::parse($fromDate)->format('d/m/Y')
                : 'البداية' }}
        </span>

        <span>
            إلى:
            {{ $toDate
                ? \Carbon\Carbon::parse($toDate)->format('d/m/Y')
                : 'حتى الآن' }}
        </span>

        <span>
            تاريخ الطباعة:
            {{ $printedAt->format('d/m/Y H:i') }}
        </span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:9%">التاريخ</th>
                <th style="width:12%">نوع المستند</th>
                <th style="width:13%">رقم المستند</th>
                <th>العميل / المورد / المخزن</th>
                <th style="width:9%">الكمية</th>
                <th style="width:10%">سعر الوحدة</th>
                <th style="width:11%">الإجمالي</th>
                <th style="width:11%">الحالة</th>
            </tr>
        </thead>

        <tbody>
        @forelse ($report['rows'] as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>

                <td class="date">
                    {{ $row['date'] }}
                </td>

                <td>{{ $row['type_label'] }}</td>

                <td class="reference">
                    {{ $row['number'] ?: '—' }}
                </td>

                <td class="text">
                    {{ $row['party'] ?: '—' }}
                </td>

                <td class="numeric">
                    {{ $qty($row['quantity']) }}
                </td>

                <td class="numeric">
                    {{ $row['unit_price'] !== null
                        ? $money($row['unit_price'])
                        : '—' }}
                </td>

                <td class="numeric">
                    {{ $row['line_total'] !== null
                        ? $money($row['line_total'])
                        : '—' }}
                </td>

                <td>
                    <span class="status">
                        {{ $row['status'] ?: '—' }}
                    </span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" style="padding:22px">
                    لا توجد حركات للصنف خلال معايير البحث المحددة.
                </td>
            </tr>
        @endforelse
        </tbody>

        <tfoot>
            <tr class="total-row">
                <td colspan="5">إجمالي الحركات</td>
                <td class="numeric">{{ $qty($report['total_quantity']) }}</td>
                <td colspan="2">عدد الحركات: {{ $report['count'] }}</td>
                <td>{{ $documentTypeLabel }}</td>
            </tr>
        </tfoot>
    </table>

    <footer class="footer">
        <span>{{ $settings->commercialName() }}</span>
        <span>الصنف: {{ $item->name }}</span>
        <span>عدد الحركات: {{ $report['count'] }}</span>
    </footer>
</main>
</body>
</html>
