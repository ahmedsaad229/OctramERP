<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>ميزان المراجعة</title>

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
            --success: #047857;
            --success-soft: #ecfdf5;
            --danger: #b91c1c;
            --danger-soft: #fef2f2;
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
            font-size: 13px;
            font-weight: 900;
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .status-card-balanced {
            border-color: #a7f3d0;
            background: var(--success-soft);
        }

        .status-card-balanced .summary-value {
            color: var(--success);
        }

        .status-card-unbalanced {
            border-color: #fecaca;
            background: var(--danger-soft);
        }

        .status-card-unbalanced .summary-value {
            color: var(--danger);
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

        thead tr:nth-child(2) th {
            background: var(--primary-dark);
        }

        tbody tr:nth-child(even) {
            background: #f8fbfe;
        }

        tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .account-name {
            text-align: right;
            font-weight: 700;
        }

        .account-code,
        .numeric {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .debit {
            color: #1d4ed8;
        }

        .credit {
            color: #9f1239;
        }

        .total-row td {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 900;
            border-bottom: 0;
        }

        .balance-result {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-top: 12px;
            padding: 10px 13px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-weight: 800;
        }

        .balance-result.balanced {
            border-color: #a7f3d0;
            background: var(--success-soft);
            color: var(--success);
        }

        .balance-result.unbalanced {
            border-color: #fecaca;
            background: var(--danger-soft);
            color: var(--danger);
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
@php
    $money = static fn (mixed $value): string => number_format((float) $value, 2);

    $displayMoney = static fn (mixed $value): string => abs((float) $value) < 0.00001
        ? '—'
        : number_format((float) $value, 2);

    $fromDate = filled($report['fromDate'] ?? null)
        ? \Carbon\Carbon::parse($report['fromDate'])->format('d/m/Y')
        : 'البداية';

    $toDate = filled($report['toDate'] ?? null)
        ? \Carbon\Carbon::parse($report['toDate'])->format('d/m/Y')
        : 'حتى الآن';

    $difference = round(
        abs(
            (float) $report['totals']['closing_debit']
            - (float) $report['totals']['closing_credit']
        ),
        2
    );
@endphp

<main class="report-page">
    <div class="toolbar">
        <button type="button" class="print-button" onclick="window.print()">
            طباعة / حفظ PDF
        </button>
    </div>

    <x-company-document-header
        :settings="$settings"
        document-title="ميزان المراجعة"
        document-number="TRIAL-BALANCE"
        :document-date="$printedAt->format('d/m/Y')"
    />

    <section class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">عدد الحسابات</div>
            <div class="summary-value">
                {{ number_format($report['rows']->count()) }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">حركة الفترة — مدين</div>
            <div class="summary-value debit">
                {{ $money($report['totals']['period_debit']) }} ج.م
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">حركة الفترة — دائن</div>
            <div class="summary-value credit">
                {{ $money($report['totals']['period_credit']) }} ج.م
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">أرصدة آخر المدة</div>
            <div class="summary-value">
                {{ $money(max(
                    $report['totals']['closing_debit'],
                    $report['totals']['closing_credit']
                )) }} ج.م
            </div>
        </div>

        <div class="summary-card {{ $report['totals']['balanced'] ? 'status-card-balanced' : 'status-card-unbalanced' }}">
            <div class="summary-label">حالة الميزان</div>
            <div class="summary-value">
                {{ $report['totals']['balanced'] ? 'متزن' : 'غير متزن' }}
            </div>
        </div>
    </section>

    <div class="period-bar">
        <div>الفترة المحاسبية</div>
        <span>{{ $fromDate }} — {{ $toDate }}</span>
    </div>

    <table aria-label="ميزان المراجعة">
        <colgroup>
            <col style="width: 8%">
            <col style="width: 24%">
            <col style="width: 11.33%">
            <col style="width: 11.33%">
            <col style="width: 11.33%">
            <col style="width: 11.33%">
            <col style="width: 11.33%">
            <col style="width: 11.35%">
        </colgroup>

        <thead>
        <tr>
            <th rowspan="2">كود الحساب</th>
            <th rowspan="2">اسم الحساب</th>
            <th colspan="2">رصيد أول المدة</th>
            <th colspan="2">حركة الفترة</th>
            <th colspan="2">رصيد آخر المدة</th>
        </tr>
        <tr>
            <th>مدين</th>
            <th>دائن</th>
            <th>مدين</th>
            <th>دائن</th>
            <th>مدين</th>
            <th>دائن</th>
        </tr>
        </thead>

        <tbody>
        @forelse ($report['rows'] as $row)
            <tr>
                <td class="account-code">{{ $row['code'] }}</td>
                <td class="account-name">{{ $row['name'] }}</td>
                <td class="numeric debit">{{ $displayMoney($row['opening_debit']) }}</td>
                <td class="numeric credit">{{ $displayMoney($row['opening_credit']) }}</td>
                <td class="numeric debit">{{ $displayMoney($row['period_debit']) }}</td>
                <td class="numeric credit">{{ $displayMoney($row['period_credit']) }}</td>
                <td class="numeric debit">{{ $displayMoney($row['closing_debit']) }}</td>
                <td class="numeric credit">{{ $displayMoney($row['closing_credit']) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" style="padding: 25px;">
                    لا توجد حركات محاسبية مطابقة للفترة المحددة.
                </td>
            </tr>
        @endforelse
        </tbody>

        <tfoot>
        <tr class="total-row">
            <td colspan="2">إجمالي ميزان المراجعة</td>
            <td class="numeric">{{ $money($report['totals']['opening_debit']) }}</td>
            <td class="numeric">{{ $money($report['totals']['opening_credit']) }}</td>
            <td class="numeric">{{ $money($report['totals']['period_debit']) }}</td>
            <td class="numeric">{{ $money($report['totals']['period_credit']) }}</td>
            <td class="numeric">{{ $money($report['totals']['closing_debit']) }}</td>
            <td class="numeric">{{ $money($report['totals']['closing_credit']) }}</td>
        </tr>
        </tfoot>
    </table>

    <section class="balance-result {{ $report['totals']['balanced'] ? 'balanced' : 'unbalanced' }}">
        <span>
            {{ $report['totals']['balanced']
                ? 'ميزان المراجعة متزن؛ إجمالي المدين يساوي إجمالي الدائن.'
                : 'تنبيه: ميزان المراجعة غير متزن.' }}
        </span>

        <span class="numeric">
            الفرق: {{ $money($difference) }} ج.م
        </span>
    </section>

    <footer class="footer">
        <span>{{ $settings->commercialName() }}</span>
        <span>
            عدد الحسابات:
            {{ number_format($report['rows']->count()) }}
        </span>
        <span>
            تاريخ الطباعة:
            <span dir="ltr">{{ $printedAt->format('d/m/Y h:i A') }}</span>
        </span>
    </footer>
</main>
</body>
</html>
