<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>الأستاذ العام - {{ $report['account']->displayName() }}</title>

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
            --debit: #1d4ed8;
            --credit: #9f1239;
            --success: #047857;
            --success-soft: #ecfdf5;
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

        .account-strip {
            display: grid;
            grid-template-columns: 110px 1fr 130px;
            gap: 8px;
            margin: 12px 0;
        }

        .account-box {
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--primary-soft);
        }

        .account-box .label {
            color: var(--muted);
            font-size: 9px;
        }

        .account-box .value {
            margin-top: 3px;
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 900;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
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

        .summary-label {
            color: var(--muted);
            font-size: 9.5px;
        }

        .summary-value {
            margin-top: 5px;
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 900;
            direction: rtl;
            unicode-bidi: isolate;
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            width: 100%;
            min-width: 0;
            overflow: hidden;
        }

        .summary-value .amount {
            direction: ltr;
            unicode-bidi: isolate;
            display: inline-block;
            min-width: 0;
            max-width: 100%;
        }

        .summary-value .currency {
            direction: rtl;
            unicode-bidi: isolate;
            flex: 0 0 auto;
            font-size: 10px;
        }

        .summary-card.debit-card .summary-value {
            color: var(--debit);
        }

        .summary-card.credit-card .summary-value {
            color: var(--credit);
        }

        .summary-card.closing-card {
            border-color: #a7f3d0;
            background: var(--success-soft);
        }

        .summary-card.closing-card .summary-value {
            color: var(--success);
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
            direction: ltr;
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

        .debit {
            color: var(--debit);
        }

        .credit {
            color: var(--credit);
        }

        .running {
            font-weight: 900;
            color: var(--primary-dark);
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

    $openingValue = max(
        (float) $report['totals']['opening_debit'],
        (float) $report['totals']['opening_credit']
    );

    $openingSide = (float) $report['totals']['opening_debit'] > 0
        ? 'مدين'
        : ((float) $report['totals']['opening_credit'] > 0 ? 'دائن' : '—');
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
        document-title="الأستاذ العام"
        :document-number="$report['account']->code"
        :document-date="$printedAt->format('d/m/Y')"
    />

    <section class="account-strip">
        <div class="account-box">
            <div class="label">كود الحساب</div>
            <div class="value reference">{{ $report['account']->code }}</div>
        </div>

        <div class="account-box">
            <div class="label">اسم الحساب</div>
            <div class="value">{{ $report['account']->displayName() }}</div>
        </div>

        <div class="account-box">
            <div class="label">عدد الحركات</div>
            <div class="value">{{ $report['totals']['transaction_count'] }}</div>
        </div>
    </section>

    <section class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">رصيد أول المدة</div>
            <div class="summary-value">
                <span class="amount">{{ $money($openingValue) }}</span>
                <span class="currency">ج.م</span>
                @if ($openingSide !== '—')
                    <span>{{ $openingSide }}</span>
                @endif
            </div>
        </div>

        <div class="summary-card debit-card">
            <div class="summary-label">حركة مدين</div>
            <div class="summary-value">
                <span class="amount">{{ $money($report['totals']['period_debit']) }}</span>
                <span class="currency">ج.م</span>
            </div>
        </div>

        <div class="summary-card credit-card">
            <div class="summary-label">حركة دائن</div>
            <div class="summary-value">
                <span class="amount">{{ $money($report['totals']['period_credit']) }}</span>
                <span class="currency">ج.م</span>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">إجمالي الحركة</div>
            <div class="summary-value">
                <span class="amount">{{ $money(
                    (float) $report['totals']['period_debit']
                    + (float) $report['totals']['period_credit']
                ) }}</span>
                <span class="currency">ج.م</span>
            </div>
        </div>

        <div class="summary-card closing-card">
            <div class="summary-label">رصيد آخر المدة</div>
            <div class="summary-value">
                <span class="amount">{{ $money($report['totals']['closing_balance']) }}</span>
                <span class="currency">ج.م</span>
                <span>{{ $report['totals']['closing_side'] }}</span>
            </div>
        </div>
    </section>

    <div class="period-bar">
        <span>
            من:
            {{ $report['fromDate']
                ? \Carbon\Carbon::parse($report['fromDate'])->format('d/m/Y')
                : 'البداية' }}
        </span>

        <span>
            إلى:
            {{ $report['toDate']
                ? \Carbon\Carbon::parse($report['toDate'])->format('d/m/Y')
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
                <th style="width: 4%">#</th>
                <th style="width: 9%">التاريخ</th>
                <th style="width: 12%">نوع المستند</th>
                <th style="width: 12%">رقم المستند</th>
                <th>البيان</th>
                <th style="width: 10%">مدين</th>
                <th style="width: 10%">دائن</th>
                <th style="width: 11%">الرصيد</th>
                <th style="width: 7%">طبيعة الرصيد</th>
            </tr>
        </thead>

        <tbody>
        @forelse ($report['rows'] as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>

                <td class="date">
                    {{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}
                </td>

                <td>{{ $row['source_label'] }}</td>

                <td class="reference">
                    {{ $row['document_number'] ?: '—' }}
                </td>

                <td class="text">
                    {{ $row['description'] ?: '—' }}
                </td>

                <td class="numeric debit">
                    {{ (float) $row['debit'] > 0
                        ? $money($row['debit'])
                        : '—' }}
                </td>

                <td class="numeric credit">
                    {{ (float) $row['credit'] > 0
                        ? $money($row['credit'])
                        : '—' }}
                </td>

                <td class="numeric running">
                    {{ $money($row['running_balance']) }}
                </td>

                <td>{{ $row['running_side'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" style="padding: 22px">
                    لا توجد حركات لهذا الحساب خلال الفترة المحددة.
                </td>
            </tr>
        @endforelse
        </tbody>

        <tfoot>
            <tr class="total-row">
                <td colspan="5">إجمالي حركة الفترة</td>

                <td class="numeric debit">
                    {{ $money($report['totals']['period_debit']) }}
                </td>

                <td class="numeric credit">
                    {{ $money($report['totals']['period_credit']) }}
                </td>

                <td class="numeric">
                    {{ $money($report['totals']['closing_balance']) }}
                </td>

                <td>{{ $report['totals']['closing_side'] }}</td>
            </tr>
        </tfoot>
    </table>

    <footer class="footer">
        <span>{{ $settings->commercialName() }}</span>
        <span>الحساب: {{ $report['account']->displayName() }}</span>
        <span>عدد الحركات: {{ $report['totals']['transaction_count'] }}</span>
    </footer>
</main>
</body>
</html>