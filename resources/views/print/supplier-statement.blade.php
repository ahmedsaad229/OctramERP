<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>كشف حساب مورد - {{ $report['supplier']->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0f3; color: #1f2937; font-family: Arial, Tahoma, sans-serif; }
        .statement { width: min(210mm, calc(100% - 32px)); min-height: 277mm; margin: 24px auto; padding: 10mm; background: #fff; box-shadow: 0 6px 24px rgb(15 23 42 / 12%); font-size: 11px; }
        .toolbar { margin-bottom: 12px; } .print-button { border: 0; border-radius: 6px; padding: 8px 16px; background: #2563eb; color: #fff; cursor: pointer; }
        .document-header { margin-bottom: 14px; padding: 14px 16px; border: 0; border-bottom: 2px solid #2563eb; border-radius: 0; }
        .company-document-title { color: #1d4ed8; font-size: 22px; font-weight: 700; }
        .info, .totals { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 12px 0; }
        .box { border: 1px solid #cbd5e1; padding: 8px; } .label { color: #64748b; } .value { margin-top: 3px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; } thead { display: table-header-group; }
        tr { break-inside: avoid; page-break-inside: avoid; } th, td { border: 1px solid #cbd5e1; padding: 6px 5px; vertical-align: middle; }
        th { background: #eff6ff; color: #1e3a8a; } .money, .date, .reference { direction: ltr; unicode-bidi: isolate; white-space: nowrap; text-align: center; }
        .footer { margin-top: 14px; display: flex; justify-content: space-between; color: #64748b; }
        .signatures { display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px; margin-top: 28px; text-align: center; }
        @media print { body { background: #fff; } .statement { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; } .toolbar { display: none; } }
    </style>
</head>
<body>
    <main class="statement">
        <div class="toolbar"><button type="button" class="print-button" onclick="window.print()">طباعة / حفظ PDF</button></div>
        <x-company-document-header class="document-header" :settings="$settings" document-title="كشف حساب مورد"
            :document-number="$report['supplier']->code ?: '—'" :document-date="$printedAt->format('d/m/Y')" />

        <section class="info">
            <div class="box"><div class="label">المورد</div><div class="value">{{ $report['supplier']->name }}</div></div>
            <div class="box"><div class="label">كود المورد</div><div class="value reference">{{ $report['supplier']->code ?: '—' }}</div></div>
            <div class="box"><div class="label">الفترة</div><div class="value date">{{ $report['fromDate']?->format('d/m/Y') ?? 'البداية' }} — {{ $report['toDate']?->format('d/m/Y') ?? 'حتى الآن' }}</div></div>
        </section>
        <section class="totals">
            <div class="box"><div class="label">الرصيد الافتتاحي</div><div class="value money">{{ $money($report['openingBalance']) }}</div></div>
            <div class="box"><div class="label">إجمالي المشتريات</div><div class="value money">{{ $money($report['totalPurchases']) }}</div></div>
            <div class="box"><div class="label">إجمالي المسدد</div><div class="value money">{{ $money($report['totalPaid']) }}</div></div>
        </section>

        <table>
            <thead><tr><th>التاريخ</th><th>نوع المستند</th><th>رقم المستند</th><th>البيان</th><th>مشتريات</th><th>مسدد</th><th>الرصيد</th></tr></thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td class="date">{{ $row['date'] }}</td><td>{{ $row['typeLabel'] }}</td><td class="reference">{{ $row['reference'] }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td class="money">{{ $row['purchases'] > 0 ? $money($row['purchases']) : '—' }}</td>
                        <td class="money">{{ $row['paid'] > 0 ? $money($row['paid']) : '—' }}</td>
                        <td class="money">{{ $money($row['runningBalance']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center; padding:20px;">لا توجد حركات لهذا المورد خلال الفترة المحددة.</td></tr>
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
