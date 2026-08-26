<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>قائمة الدخل</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 9mm;
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
            --warning-soft: #fff7ed;
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
            width: min(210mm, calc(100% - 30px));
            min-height: 277mm;
            margin: 20px auto;
            padding: 9mm;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 28px rgb(15 23 42 / 12%);
            font-size: 10.5px;
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
                minmax(155px, .75fr)
                minmax(280px, 1.45fr)
                minmax(125px, .6fr) !important;
            align-items: center !important;
            gap: 16px !important;
            direction: rtl !important;
        }

        .company-document-title {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 44px !important;
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
            font-size: 18px !important;
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
            max-width: 130px !important;
            max-height: 76px !important;
            object-fit: contain !important;
        }

        .period-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin: 12px 0;
        }

        .period-box {
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--primary-soft);
            text-align: center;
        }

        .period-box .label {
            color: var(--muted);
            font-size: 9px;
        }

        .period-box .value {
            margin-top: 3px;
            color: var(--primary-dark);
            font-weight: 900;
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 9px;
            margin: 13px 0;
        }

        .summary-card {
            min-height: 78px;
            padding: 10px 9px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: linear-gradient(180deg, #fff, #f8fbfe);
            text-align: center;
            overflow: hidden;
        }

        .summary-label {
            color: var(--muted);
            font-size: 9.5px;
        }

        .summary-value {
            margin-top: 6px;
            color: var(--primary-dark);
            font-size: 11.5px;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            width: 100%;
            min-width: 0;
            white-space: nowrap;
        }

        .summary-value .amount {
            direction: ltr;
            unicode-bidi: isolate;
            display: inline-block;
            min-width: 0;
        }

        .summary-value .currency {
            flex: 0 0 auto;
            font-size: 9.5px;
        }

        .summary-card.profit-card {
            border-color: #a7f3d0;
            background: var(--success-soft);
        }

        .summary-card.profit-card .summary-value {
            color: var(--success);
        }

        .summary-card.loss-card {
            border-color: #fecaca;
            background: var(--danger-soft);
        }

        .summary-card.loss-card .summary-value {
            color: var(--danger);
        }

        .statement-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }

        .statement-table thead {
            display: table-header-group;
        }

        .statement-table th,
        .statement-table td {
            padding: 7px 8px;
            border-left: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .statement-table th:last-child,
        .statement-table td:last-child {
            border-left: 0;
        }

        .statement-table th {
            background: var(--primary);
            color: #fff;
            font-weight: 800;
            text-align: center;
        }

        .statement-table tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .statement-table .description {
            text-align: right;
        }

        .statement-table .amount {
            width: 28%;
            text-align: center;
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .section-row td {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 900;
        }

        .section-row .amount {
            font-size: 11px;
        }

        .detail-row:nth-child(even) td {
            background: #fbfdff;
        }

        .detail-row .description {
            padding-right: 24px;
        }

        .subtotal-row td {
            background: #f8fafc;
            color: var(--primary-dark);
            font-weight: 900;
        }

        .net-row td {
            border-bottom: 0;
            font-size: 12px;
            font-weight: 900;
        }

        .net-row.profit td {
            background: var(--success-soft);
            color: var(--success);
        }

        .net-row.loss td {
            background: var(--danger-soft);
            color: var(--danger);
        }

        .note {
            margin-top: 10px;
            padding: 8px 11px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #f8fafc;
            color: var(--muted);
            font-size: 9px;
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
    $displayAmount = static fn ($value): string => abs((float) $value) < 0.005
        ? '—'
        : number_format((float) $value, 2);

    $isProfit = (bool) $report['totals']['is_profit'];
    $netProfit = (float) $report['totals']['net_profit'];
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
        document-title="قائمة الدخل"
        document-number="—"
        :document-date="$printedAt->format('d/m/Y')"
    />

    <section class="period-strip">
        <div class="period-box">
            <div class="label">من تاريخ</div>
            <div class="value">
                {{ $report['fromDate']
                    ? \Carbon\Carbon::parse($report['fromDate'])->format('d/m/Y')
                    : 'البداية' }}
            </div>
        </div>

        <div class="period-box">
            <div class="label">إلى تاريخ</div>
            <div class="value">
                {{ $report['toDate']
                    ? \Carbon\Carbon::parse($report['toDate'])->format('d/m/Y')
                    : 'حتى الآن' }}
            </div>
        </div>

        <div class="period-box">
            <div class="label">تاريخ الطباعة</div>
            <div class="value">{{ $printedAt->format('d/m/Y H:i') }}</div>
        </div>
    </section>

    <section class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">إجمالي الإيرادات</div>
            <div class="summary-value">
                <span class="amount">{{ $money($report['totals']['revenue']) }}</span>
                <span class="currency">ج.م</span>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">مجمل الربح</div>
            <div class="summary-value">
                <span class="amount">{{ $money($report['totals']['gross_profit']) }}</span>
                <span class="currency">ج.م</span>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">الربح التشغيلي</div>
            <div class="summary-value">
                <span class="amount">{{ $money($report['totals']['operating_profit']) }}</span>
                <span class="currency">ج.م</span>
            </div>
        </div>

        <div class="summary-card {{ $isProfit ? 'profit-card' : 'loss-card' }}">
            <div class="summary-label">
                {{ $isProfit ? 'صافي الربح' : 'صافي الخسارة' }}
            </div>
            <div class="summary-value">
                <span class="amount">{{ $money(abs($netProfit)) }}</span>
                <span class="currency">ج.م</span>
            </div>
        </div>
    </section>

    <table class="statement-table">
        <thead>
            <tr>
                <th>البيان</th>
                <th style="width: 28%">المبلغ</th>
            </tr>
        </thead>

        <tbody>
        @foreach ($report['sections'] as $section)
            <tr class="section-row">
                <td class="description">{{ $section['label'] }}</td>
                <td class="amount">{{ $displayAmount($section['total']) }}</td>
            </tr>

            @forelse ($section['rows'] as $row)
                <tr class="detail-row">
                    <td class="description">
                        {{ $row['code'] }} — {{ $row['name'] }}
                    </td>
                    <td class="amount">
                        {{ $displayAmount($row['amount']) }}
                    </td>
                </tr>
            @empty
                @if ($report['details'])
                    <tr class="detail-row">
                        <td class="description" style="color: var(--muted)">
                            لا توجد حسابات ذات حركة ضمن هذا القسم.
                        </td>
                        <td class="amount">—</td>
                    </tr>
                @endif
            @endforelse

            @if ($section['type'] === \App\Models\Account::TYPE_COST)
                <tr class="subtotal-row">
                    <td class="description">مجمل الربح</td>
                    <td class="amount">
                        {{ $displayAmount($report['totals']['gross_profit']) }}
                    </td>
                </tr>
            @elseif ($section['type'] === \App\Models\Account::TYPE_EXPENSE)
                <tr class="subtotal-row">
                    <td class="description">الربح التشغيلي</td>
                    <td class="amount">
                        {{ $displayAmount($report['totals']['operating_profit']) }}
                    </td>
                </tr>
            @endif
        @endforeach
        </tbody>

        <tfoot>
            <tr class="net-row {{ $isProfit ? 'profit' : 'loss' }}">
                <td class="description">
                    {{ $isProfit ? 'صافي الربح عن الفترة' : 'صافي الخسارة عن الفترة' }}
                </td>
                <td class="amount">
                    {{ $money(abs($netProfit)) }} ج.م
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="note">
        تعتمد قائمة الدخل على القيود المحاسبية المرحلة خلال الفترة المحددة وتصنيف الحسابات في دليل الحسابات.
    </div>

    <footer class="footer">
        <span>{{ $settings->commercialName() }}</span>
        <span>قائمة الدخل</span>
        <span>{{ $isProfit ? 'صافي ربح' : 'صافي خسارة' }}: {{ $money(abs($netProfit)) }} ج.م</span>
    </footer>
</main>
</body>
</html>