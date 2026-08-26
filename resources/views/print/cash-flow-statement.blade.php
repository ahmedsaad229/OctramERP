<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>قائمة التدفقات النقدية</title>

    <style>
        @page { size: A4 portrait; margin: 9mm; }
        :root { --primary:#123b67; --primary-dark:#0d2f54; --soft:#edf5fc; --border:#bfd0e2; --text:#172033; --muted:#64748b; --success:#047857; --success-soft:#ecfdf5; --danger:#b91c1c; --danger-soft:#fef2f2; }
        * { box-sizing:border-box; }
        body { margin:0; background:#eef2f7; color:var(--text); font-family:Arial,Tahoma,sans-serif; line-height:1.5; }
        .page { width:min(210mm,calc(100% - 30px)); min-height:277mm; margin:20px auto; padding:9mm; background:#fff; border-radius:10px; box-shadow:0 8px 28px rgb(15 23 42 / 12%); font-size:10.5px; }
        .toolbar { margin-bottom:12px; }
        .print-button { border:0; border-radius:7px; padding:9px 18px; background:#2563eb; color:#fff; cursor:pointer; font-weight:800; }
        .company-document-header { margin-bottom:15px!important; padding:14px 16px!important; border:1px solid var(--border)!important; border-top:5px solid var(--primary)!important; border-radius:10px!important; background:#fff!important; }
        .company-document-layout { display:grid!important; grid-template-columns:minmax(155px,.75fr) minmax(280px,1.45fr) minmax(125px,.6fr)!important; align-items:center!important; gap:16px!important; direction:rtl!important; }
        .company-document-title { display:flex!important; align-items:center!important; justify-content:center!important; min-height:44px!important; margin-bottom:8px!important; padding:8px 14px!important; border-radius:8px!important; background:var(--primary)!important; color:#fff!important; font-size:20px!important; font-weight:800!important; text-align:center!important; }
        .company-document-company { text-align:center!important; }
        .company-document-company-name { color:var(--primary)!important; font-size:18px!important; font-weight:800!important; white-space:nowrap!important; }
        .company-document-logo-wrap { display:flex!important; justify-content:flex-start!important; align-items:center!important; width:100%!important; direction:ltr!important; }
        .company-document-logo { max-width:130px!important; max-height:76px!important; object-fit:contain!important; }
        .period { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; margin:12px 0; }
        .period-box { padding:9px 12px; border:1px solid var(--border); border-radius:8px; background:var(--soft); text-align:center; }
        .period-box .label { color:var(--muted); font-size:9px; }
        .period-box .value { margin-top:3px; color:var(--primary-dark); font-weight:900; direction:ltr; unicode-bidi:isolate; white-space:nowrap; }
        .summary { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:8px; margin:13px 0; }
        .card { min-height:76px; padding:10px 7px; border:1px solid var(--border); border-radius:8px; text-align:center; background:linear-gradient(180deg,#fff,#f8fbfe); overflow:hidden; }
        .card .label { color:var(--muted); font-size:8.8px; }
        .card .value { margin-top:6px; color:var(--primary-dark); font-size:10px; font-weight:900; display:flex; align-items:center; justify-content:center; gap:3px; white-space:nowrap; }
        .card .amount { direction:ltr; unicode-bidi:isolate; }
        .card.in .value { color:var(--success); }
        .card.out .value { color:var(--danger); }
        .status { margin:10px 0 13px; padding:9px 12px; border:1px solid; border-radius:8px; text-align:center; font-weight:900; }
        .status.ok { border-color:#a7f3d0; background:var(--success-soft); color:var(--success); }
        .status.bad { border-color:#fecaca; background:var(--danger-soft); color:var(--danger); }
        table { width:100%; border-collapse:separate; border-spacing:0; table-layout:fixed; border:1px solid var(--border); border-radius:8px; overflow:hidden; margin-bottom:13px; }
        thead { display:table-header-group; }
        th,td { padding:7px 8px; border-left:1px solid var(--border); border-bottom:1px solid var(--border); vertical-align:middle; }
        th:last-child,td:last-child { border-left:0; }
        th { background:var(--primary); color:#fff; font-weight:800; }
        tr { break-inside:avoid; page-break-inside:avoid; }
        td.description { text-align:right; }
        td.amount { width:28%; text-align:center; direction:ltr; unicode-bidi:isolate; white-space:nowrap; }
        tr.section td { background:var(--soft); color:var(--primary-dark); font-weight:900; }
        tr.category td { background:#f8fafc; font-weight:800; }
        tr.detail:nth-child(even) td { background:#fbfdff; }
        tr.detail td.description { padding-right:24px; font-size:9.5px; }
        tr.net td { background:var(--soft); color:var(--primary-dark); font-weight:900; }
        tr.closing td { border-bottom:0; font-size:12px; font-weight:900; background:var(--success-soft); color:var(--success); }
        .note { margin-top:10px; padding:8px 11px; border:1px solid var(--border); border-radius:8px; background:#f8fafc; color:var(--muted); font-size:9px; }
        .footer { display:flex; justify-content:space-between; gap:15px; margin-top:22px; padding-top:8px; border-top:1px solid var(--border); color:var(--muted); font-size:9px; }
        @media print { body{background:#fff;print-color-adjust:exact;-webkit-print-color-adjust:exact}.page{width:auto;min-height:0;margin:0;padding:0;border-radius:0;box-shadow:none}.toolbar{display:none} }
    </style>

    @include('print.partials.report-style')
</head>
<body>
@php
    $money = static fn ($value): string => number_format((float) $value, 2);
    $display = static fn ($value): string => abs((float) $value) < 0.005 ? '—' : number_format(abs((float) $value), 2);
@endphp

<main class="page">
    <div class="toolbar"><button class="print-button" onclick="window.print()">طباعة / حفظ PDF</button></div>

    <x-company-document-header
        class="company-document-header"
        :settings="$settings"
        document-title="قائمة التدفقات النقدية"
        document-number="—"
        :document-date="$printedAt->format('d/m/Y')"
    />

    <section class="period">
        <div class="period-box"><div class="label">من تاريخ</div><div class="value">{{ $report['fromDate'] ? \Carbon\Carbon::parse($report['fromDate'])->format('d/m/Y') : 'البداية' }}</div></div>
        <div class="period-box"><div class="label">إلى تاريخ</div><div class="value">{{ $report['toDate'] ? \Carbon\Carbon::parse($report['toDate'])->format('d/m/Y') : 'حتى الآن' }}</div></div>
        <div class="period-box"><div class="label">تاريخ الطباعة</div><div class="value">{{ $printedAt->format('d/m/Y H:i') }}</div></div>
    </section>

    <section class="summary">
        <div class="card"><div class="label">رصيد أول الفترة</div><div class="value"><span class="amount">{{ $money($report['totals']['opening_balance']) }}</span><span>ج.م</span></div></div>
        <div class="card in"><div class="label">إجمالي الداخل</div><div class="value"><span class="amount">{{ $money($report['totals']['inflows']) }}</span><span>ج.م</span></div></div>
        <div class="card out"><div class="label">إجمالي الخارج</div><div class="value"><span class="amount">{{ $money($report['totals']['outflows']) }}</span><span>ج.م</span></div></div>
        <div class="card"><div class="label">صافي التغير</div><div class="value"><span class="amount">{{ $money($report['totals']['net_change']) }}</span><span>ج.م</span></div></div>
        <div class="card"><div class="label">رصيد آخر الفترة</div><div class="value"><span class="amount">{{ $money($report['totals']['closing_balance']) }}</span><span>ج.م</span></div></div>
    </section>

    <div class="status {{ $report['totals']['balanced'] ? 'ok' : 'bad' }}">
        @if ($report['totals']['balanced'])
            التقرير متطابق مع رصيد النقدية والبنوك بدفتر الأستاذ
        @else
            يوجد فرق في المطابقة قدره {{ $money(abs($report['totals']['difference'])) }} ج.م
        @endif
    </div>

    <table>
        <thead><tr><th>البيان</th><th style="width:28%">المبلغ</th></tr></thead>
        <tbody>
        @foreach ($report['sections'] as $section)
            <tr class="section"><td class="description">{{ $section['label'] }}</td><td class="amount">{{ $money($section['net']) }}</td></tr>

            @forelse ($section['categories'] as $category)
                <tr class="category"><td class="description">{{ $category['label'] }}</td><td class="amount">{{ $category['amount'] >= 0 ? $display($category['amount']) : '('.$display($category['amount']).')' }}</td></tr>

                @if ($report['details'])
                    @foreach ($category['rows'] as $row)
                        <tr class="detail">
                            <td class="description">{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }} — {{ $row['document_number'] ?: 'بدون رقم' }} — {{ $row['description'] }}</td>
                            <td class="amount">{{ $row['amount'] >= 0 ? $display($row['amount']) : '('.$display($row['amount']).')' }}</td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr class="detail"><td class="description" style="color:var(--muted)">لا توجد تدفقات ضمن هذا النشاط في الفترة المحددة.</td><td class="amount">—</td></tr>
            @endforelse

            <tr class="net"><td class="description">صافي {{ $section['label'] }}</td><td class="amount">{{ $money($section['net']) }}</td></tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr class="net"><td class="description">صافي التغير في النقدية</td><td class="amount">{{ $money($report['totals']['net_change']) }}</td></tr>
            <tr class="closing"><td class="description">رصيد النقدية والبنوك في آخر الفترة</td><td class="amount">{{ $money($report['totals']['closing_balance']) }} ج.م</td></tr>
        </tfoot>
    </table>

    <div class="note">
        تعتمد القائمة على القيود المحاسبية المرحلة إلى حساب النقدية والخزائن والحسابات البنكية، وتستبعد التحويلات الداخلية التي لا تغيّر إجمالي النقدية.
    </div>

    <footer class="footer">
        <span>{{ $settings->commercialName() }}</span>
        <span>قائمة التدفقات النقدية</span>
        <span>عدد الحركات: {{ $report['totals']['movement_count'] }}</span>
    </footer>
</main>
</body>
</html>
