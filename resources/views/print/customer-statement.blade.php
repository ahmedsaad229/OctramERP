<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>كشف حساب عميل - {{ $report['customer']->name }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0f3; color: #1f2937; font-family: Arial, Tahoma, sans-serif; }
        .statement { width: min(210mm, calc(100% - 32px)); min-height: 277mm; margin: 24px auto; padding: 10mm; background: #fff; box-shadow: 0 6px 24px rgb(15 23 42 / 12%); font-size: 11px; }
        .toolbar { margin-bottom: 12px; }
        .print-button { border: 0; border-radius: 6px; padding: 8px 16px; background: #2563eb; color: white; cursor: pointer; }
        .document-header { margin-bottom: 14px; padding: 14px 16px; border: 0; border-bottom: 2px solid #2563eb; border-radius: 0; }
        .company-document-title { color: #1d4ed8; font-size: 22px; font-weight: 700; }
        .info, .totals { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 12px 0; }
        .box { border: 1px solid #cbd5e1; padding: 8px; }
        .label { color: #64748b; }
        .value { margin-top: 3px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 5px; vertical-align: middle; }
        th { background: #eff6ff; color: #1e3a8a; }
        .money, .date, .reference { direction: ltr; unicode-bidi: isolate; white-space: nowrap; text-align: center; }
        .footer { margin-top: 14px; display: flex; justify-content: space-between; color: #64748b; }
        .signatures { display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px; margin-top: 28px; text-align: center; }
        @media print {
            body { background: #fff; }
            .statement { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
            .toolbar { display: none; }
        }
    </style>
    @include('print.partials.report-style')
<style>
    /* CUSTOMER-STATEMENT-OCTRAM-V2 */

    :root {
        --primary: #123b67;
        --primary-dark: #0d2f54;
        --primary-soft: #edf5fc;
        --border: #bfd0e2;
        --text: #172033;
        --muted: #64748b;
    }

    .statement {
        width: min(210mm, calc(100% - 30px)) !important;
        min-height: 281mm !important;
        margin: 20px auto !important;
        padding: 9mm !important;
        border-radius: 10px !important;
        background: #fff !important;
        box-shadow: 0 8px 28px rgb(15 23 42 / 12%) !important;
        font-size: 11px !important;
    }

    .toolbar {
        display: flex !important;
        justify-content: flex-start !important;
        margin-bottom: 12px !important;
    }

    .print-button {
        border: 0 !important;
        border-radius: 7px !important;
        padding: 9px 18px !important;
        background: #2563eb !important;
        color: #fff !important;
        cursor: pointer !important;
        font-weight: 700 !important;
        box-shadow: 0 4px 12px rgb(37 99 235 / 22%) !important;
    }

    .company-document-header,
    .document-header {
        margin-bottom: 18px !important;
        padding: 15px 17px !important;
        border: 1px solid var(--border) !important;
        border-top: 5px solid var(--primary) !important;
        border-radius: 10px !important;
        background: #fff !important;
    }

    .company-document-layout {
        display: grid !important;
        grid-template-columns:
            minmax(190px, .8fr)
            minmax(300px, 1.5fr)
            minmax(135px, .65fr) !important;
        align-items: center !important;
        gap: 18px !important;
        direction: rtl !important;
    }

    .company-document-details {
        min-width: 0 !important;
        text-align: right !important;
    }

    .company-document-title {
        display: block !important;
        width: 100% !important;
        margin: 0 0 8px !important;
        padding: 8px 14px !important;
        border-radius: 8px !important;
        background: var(--primary) !important;
        color: #fff !important;
        font-size: 20px !important;
        font-weight: 800 !important;
        text-align: center !important;
        white-space: nowrap !important;
    }

    .company-document-meta {
        display: grid !important;
        gap: 4px !important;
    }

    .company-document-meta > div {
        display: grid !important;
        grid-template-columns: 80px minmax(0, 1fr) !important;
        gap: 7px !important;
        align-items: center !important;
    }

    .company-document-meta span {
        color: var(--muted) !important;
        font-size: 10px !important;
    }

    .company-document-meta strong {
        color: var(--text) !important;
        font-size: 10.5px !important;
    }

    .company-document-company {
        min-width: 0 !important;
        text-align: center !important;
    }

    .company-document-company-name {
        color: var(--primary) !important;
        font-size: 19px !important;
        font-weight: 800 !important;
        white-space: nowrap !important;
    }

    .company-document-activity {
        margin-top: 2px !important;
        color: #475569 !important;
        font-size: 10.5px !important;
        font-weight: 700 !important;
    }

    .company-document-contact-lines {
        margin-top: 6px !important;
        color: #475569 !important;
        font-size: 9px !important;
        line-height: 1.55 !important;
    }

    .company-document-legal {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
        gap: 8px !important;
    }

    .company-document-logo-wrap {
        display: flex !important;
        justify-content: flex-start !important;
        align-items: center !important;
        justify-self: start !important;
        width: 100% !important;
        direction: ltr !important;
    }

    .company-document-logo {
        width: auto !important;
        max-width: 135px !important;
        height: auto !important;
        max-height: 82px !important;
        object-fit: contain !important;
    }

    .info,
    .totals {
        display: grid !important;
        gap: 10px !important;
        margin: 14px 0 !important;
    }

    .info {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    }

    .totals {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    }

    .box {
        min-height: 72px !important;
        padding: 11px 13px !important;
        border: 1px solid var(--border) !important;
        border-radius: 8px !important;
        background: linear-gradient(180deg, #fff, #f8fbfe) !important;
        box-shadow: 0 2px 7px rgb(15 23 42 / 5%) !important;
    }

    .label {
        color: var(--muted) !important;
        font-size: 10.5px !important;
    }

    .value {
        margin-top: 5px !important;
        color: var(--primary-dark) !important;
        font-size: 13px !important;
        font-weight: 800 !important;
    }

    table {
        width: 100% !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        table-layout: fixed !important;
        overflow: hidden !important;
        border: 1px solid var(--border) !important;
        border-radius: 8px !important;
    }

    thead {
        display: table-header-group !important;
    }

    th {
        padding: 8px 6px !important;
        border: 0 !important;
        border-left: 1px solid #7796b5 !important;
        background: var(--primary) !important;
        color: #fff !important;
        font-weight: 800 !important;
        text-align: center !important;
        white-space: nowrap !important;
    }

    td {
        padding: 8px 6px !important;
        border: 0 !important;
        border-left: 1px solid #d7e1eb !important;
        border-bottom: 1px solid #d7e1eb !important;
        vertical-align: middle !important;
    }

    th:last-child,
    td:last-child {
        border-left: 0 !important;
    }

    tbody tr:last-child td {
        border-bottom: 0 !important;
    }

    tbody tr:nth-child(even) {
        background: #f8fbfe !important;
    }

    tr {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    .money,
    .date,
    .reference {
        direction: ltr !important;
        unicode-bidi: isolate !important;
        text-align: center !important;
        white-space: nowrap !important;
    }

    .totals:last-of-type .box:first-child {
        border-color: #86b6e6 !important;
        background: var(--primary-soft) !important;
    }

    .signatures {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 85px !important;
        margin-top: 42px !important;
        padding: 0 30px !important;
        text-align: center !important;
    }

    .signatures > div {
        min-height: 65px !important;
        padding-top: 11px !important;
        border-top: 1px solid #4b6075 !important;
        color: var(--primary-dark) !important;
        font-weight: 700 !important;
    }

    .footer {
        display: flex !important;
        justify-content: space-between !important;
        gap: 12px !important;
        margin-top: 24px !important;
        padding-top: 10px !important;
        border-top: 1px solid var(--border) !important;
        color: var(--muted) !important;
        font-size: 10px !important;
    }

    @media screen and (max-width: 760px) {
        .statement {
            width: 100% !important;
            margin: 0 !important;
            padding: 15px !important;
            box-shadow: none !important;
            overflow-x: auto !important;
        }

        .company-document-layout,
        .info,
        .totals,
        .signatures {
            grid-template-columns: 1fr !important;
        }

        .company-document-logo-wrap {
            justify-content: center !important;
            justify-self: center !important;
        }

        .company-document-company-name {
            white-space: normal !important;
        }
    }

    @media print {
        body {
            background: #fff !important;
            print-color-adjust: exact !important;
            -webkit-print-color-adjust: exact !important;
        }

        .statement {
            width: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        .toolbar {
            display: none !important;
        }
    }
</style>
<style>
    /* CUSTOMER-STATEMENT-TITLE-CENTER-FIX-V2 */

    .statement .company-document-details {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        justify-content: center !important;
        width: 100% !important;
        min-width: 0 !important;
    }

    .statement .company-document-title {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        min-height: 48px !important;
        margin: 0 0 9px 0 !important;
        padding: 8px 10px !important;
        text-align: center !important;
        direction: rtl !important;
        line-height: 1.25 !important;
        white-space: nowrap !important;
    }

    .statement .company-document-meta {
        width: 100% !important;
        align-self: stretch !important;
    }
</style>
</head>
<body>
    <main class="statement">
        <div class="toolbar"><button type="button" class="print-button" onclick="window.print()">طباعة / حفظ PDF</button></div>

        <x-company-document-header
            class="document-header"
            :settings="$settings"
            document-title="كشف حساب عميل"
            :document-number="$report['customer']->code ?: '—'"
            :document-date="$printedAt->format('d/m/Y')"
        />

        <section class="info">
            <div class="box"><div class="label">العميل</div><div class="value">{{ $report['customer']->name }}</div></div>
            <div class="box"><div class="label">كود العميل</div><div class="value reference">{{ $report['customer']->code ?: '—' }}</div></div>
            <div class="box"><div class="label">الفترة</div><div class="value date">{{ $report['fromDate']?->format('d/m/Y') ?? 'البداية' }} — {{ $report['toDate']?->format('d/m/Y') ?? 'حتى الآن' }}</div></div>
        </section>

        <section class="totals">
            <div class="box"><div class="label">الرصيد الافتتاحي</div><div class="value money">{{ $money($report['openingBalance']) }}</div></div>
            <div class="box"><div class="label">إجمالي المديونية</div><div class="value money">{{ $money($report['totalDebt']) }}</div></div>
            <div class="box"><div class="label">إجمالي المسدد</div><div class="value money">{{ $money($report['totalPaid']) }}</div></div>
        </section>

        <table>
            <thead>
                <tr>
                    <th style="width: 11%">التاريخ</th>
                    <th style="width: 13%">نوع المستند</th>
                    <th style="width: 15%">رقم المستند</th>
                    <th>البيان</th>
                    <th style="width: 13%">مديونية</th>
                    <th style="width: 13%">مسدد</th>
                    <th style="width: 14%">الرصيد</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td class="date">{{ $row['date'] }}</td>
                        <td>{{ $row['typeLabel'] }}</td>
                        <td class="reference">{{ $row['reference'] }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td class="money">{{ $row['debit'] > 0 ? $money($row['debit']) : '—' }}</td>
                        <td class="money">{{ $row['credit'] > 0 ? $money($row['credit']) : '—' }}</td>
                        <td class="money">{{ $money($row['runningBalance']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center; padding:20px;">لا توجد حركات لهذا العميل خلال الفترة المحددة.</td></tr>
                @endforelse
            </tbody>
        </table>

        <section class="totals">
            <div class="box"><div class="label">الرصيد الختامي</div><div class="value money">{{ $money($report['closingBalance']) }}</div></div>
            <div class="box"><div class="label">حالة الرصيد</div><div class="value">{{ $report['statusLabel'] }}</div></div>
            <div class="box"><div class="label">عدد الحركات</div><div class="value">{{ $report['transactionCount'] }}</div></div>
        </section>

        <div class="signatures"><div>إعداد الكشف<br><br>........................</div><div>اعتماد<br><br>........................</div></div>
        <footer class="footer"><span>{{ $settings->commercialName() }}</span><span>تاريخ الطباعة: {{ $printedAt->format('d/m/Y H:i') }}</span></footer>
    </main>
</body>
</html>


