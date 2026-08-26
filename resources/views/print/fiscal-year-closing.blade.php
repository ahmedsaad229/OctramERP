<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>معاينة إقفال السنة المالية</title>
    <style>
        @page { size:A4 portrait; margin:9mm; }
        :root {
            --primary:#123b67;
            --primary-dark:#0d2f54;
            --soft:#edf5fc;
            --border:#bfd0e2;
            --text:#172033;
            --muted:#64748b;
            --success:#047857;
            --success-soft:#ecfdf5;
            --danger:#b91c1c;
            --danger-soft:#fef2f2;
            --warning:#a16207;
            --warning-soft:#fffbeb;
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            background:#eef2f7;
            color:var(--text);
            font-family:Arial,Tahoma,sans-serif;
            line-height:1.5;
        }
        .page {
            width:min(210mm,calc(100% - 30px));
            min-height:277mm;
            margin:20px auto;
            padding:9mm;
            background:#fff;
            border-radius:10px;
            box-shadow:0 8px 28px rgb(15 23 42 / 12%);
            font-size:10.5px;
        }
        .toolbar { margin-bottom:12px; }
        .print-button {
            border:0;
            border-radius:7px;
            padding:9px 18px;
            background:#2563eb;
            color:#fff;
            cursor:pointer;
            font-weight:800;
        }
        .company-document-header {
            margin-bottom:13px!important;
            padding:14px 16px!important;
            border:1px solid var(--border)!important;
            border-top:5px solid var(--primary)!important;
            border-radius:10px!important;
            background:#fff!important;
        }
        .company-document-layout {
            display:grid!important;
            grid-template-columns:minmax(155px,.75fr) minmax(280px,1.45fr) minmax(125px,.6fr)!important;
            align-items:center!important;
            gap:16px!important;
            direction:rtl!important;
        }
        .company-document-title {
            display:flex!important;
            align-items:center!important;
            justify-content:center!important;
            min-height:44px!important;
            margin-bottom:8px!important;
            padding:8px 14px!important;
            border-radius:8px!important;
            background:var(--primary)!important;
            color:#fff!important;
            font-size:18px!important;
            font-weight:800!important;
            text-align:center!important;
        }
        .company-document-company { text-align:center!important; }
        .company-document-company-name {
            color:var(--primary)!important;
            font-size:18px!important;
            font-weight:800!important;
            white-space:nowrap!important;
        }
        .company-document-logo-wrap {
            display:flex!important;
            justify-content:flex-start!important;
            align-items:center!important;
            width:100%!important;
            direction:ltr!important;
        }
        .company-document-logo {
            max-width:130px!important;
            max-height:76px!important;
            object-fit:contain!important;
        }
        .preview-banner {
            margin:10px 0 12px;
            padding:8px 12px;
            border:1px solid #f3d58a;
            border-radius:8px;
            background:var(--warning-soft);
            color:var(--warning);
            font-weight:800;
            text-align:center;
        }
        .period {
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:8px;
            margin:12px 0;
        }
        .period-box {
            padding:9px 12px;
            border:1px solid var(--border);
            border-radius:8px;
            background:var(--soft);
            text-align:center;
        }
        .period-box .label { color:var(--muted); font-size:9px; }
        .period-box .value {
            margin-top:3px;
            color:var(--primary-dark);
            font-weight:900;
            direction:ltr;
            unicode-bidi:isolate;
            white-space:nowrap;
        }
        .summary {
            display:grid;
            grid-template-columns:repeat(4,minmax(0,1fr));
            gap:8px;
            margin:13px 0;
        }
        .card {
            min-height:76px;
            padding:10px 8px;
            border:1px solid var(--border);
            border-radius:8px;
            text-align:center;
            background:linear-gradient(180deg,#fff,#f8fbfe);
            overflow:hidden;
        }
        .card .label { color:var(--muted); font-size:9px; }
        .card .value {
            margin-top:6px;
            color:var(--primary-dark);
            font-size:11px;
            font-weight:900;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:4px;
            white-space:nowrap;
        }
        .card .amount { direction:ltr; unicode-bidi:isolate; }
        .profit { border-color:#a7f3d0; background:var(--success-soft); }
        .loss { border-color:#fecaca; background:var(--danger-soft); }
        .profit .value { color:var(--success); }
        .loss .value { color:var(--danger); }
        .status-ok { border-color:#a7f3d0; background:var(--success-soft); }
        .status-bad { border-color:#fecaca; background:var(--danger-soft); }
        .status-ok .value { color:var(--success); }
        .status-bad .value { color:var(--danger); }
        .section-title {
            margin:15px 0 7px;
            padding:7px 10px;
            border-right:4px solid var(--primary);
            background:var(--soft);
            color:var(--primary-dark);
            font-size:11px;
            font-weight:900;
        }
        table {
            width:100%;
            border-collapse:separate;
            border-spacing:0;
            table-layout:fixed;
            border:1px solid var(--border);
            border-radius:8px;
            overflow:hidden;
        }
        thead { display:table-header-group; }
        th,td {
            padding:7px 8px;
            border-left:1px solid var(--border);
            border-bottom:1px solid var(--border);
            vertical-align:middle;
        }
        th:last-child,td:last-child { border-left:0; }
        th { background:var(--primary); color:#fff; font-weight:800; }
        tr { break-inside:avoid; page-break-inside:avoid; }
        td.code {
            width:18%;
            text-align:center;
            direction:ltr;
            unicode-bidi:isolate;
            white-space:nowrap;
        }
        td.description { text-align:right; }
        td.amount {
            width:20%;
            text-align:center;
            direction:ltr;
            unicode-bidi:isolate;
            white-space:nowrap;
        }
        tr.detail:nth-child(even) td { background:#fbfdff; }
        tr.retained td {
            background:#f8fafc;
            color:var(--primary-dark);
            font-weight:900;
        }
        tfoot td {
            background:var(--soft);
            color:var(--primary-dark);
            font-weight:900;
            font-size:11px;
        }
        .warnings {
            margin-top:10px;
            padding:9px 12px;
            border:1px solid #fecaca;
            border-radius:8px;
            background:var(--danger-soft);
            color:var(--danger);
        }
        .note {
            margin-top:10px;
            padding:8px 11px;
            border:1px solid var(--border);
            border-radius:8px;
            background:#f8fafc;
            color:var(--muted);
            font-size:9px;
        }
        .signatures {
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:20px;
            margin-top:28px;
            text-align:center;
            font-size:9px;
            color:var(--muted);
        }
        .signature {
            padding-top:25px;
            border-top:1px solid var(--border);
        }
        .footer {
            display:flex;
            justify-content:space-between;
            gap:15px;
            margin-top:18px;
            padding-top:8px;
            border-top:1px solid var(--border);
            color:var(--muted);
            font-size:9px;
        }
        @media print {
            body { background:#fff; print-color-adjust:exact; -webkit-print-color-adjust:exact; }
            .page { width:auto; min-height:0; margin:0; padding:0; border-radius:0; box-shadow:none; }
            .toolbar { display:none; }
        }
    </style>
    @include('print.partials.report-style')
</head>
<body>
@php
    $money = static fn ($value): string => number_format((float) $value, 2);
    $display = static fn ($value): string => abs((float) $value) < 0.005 ? '—' : number_format((float) $value, 2);
    $year = $preview['year'];
    $isProfit = (float) $preview['net_result'] >= 0;
    $balanced = abs((float) $preview['total_debit'] - (float) $preview['total_credit']) < 0.01;
@endphp

<main class="page">
    <div class="toolbar">
        <button class="print-button" onclick="window.print()">طباعة / حفظ PDF</button>
    </div>

    <x-company-document-header
        class="company-document-header"
        :settings="$settings"
        document-title="معاينة إقفال السنة المالية"
        :document-number="$year->name"
        :document-date="$printedAt->format('d/m/Y')"
    />

    @if (! $year->isClosed())
        <div class="preview-banner">
            معاينة فقط — السنة المالية ما زالت مفتوحة ولم يتم تنفيذ الإقفال
        </div>
    @else
        <div class="preview-banner" style="border-color:#a7f3d0;background:var(--success-soft);color:var(--success);">
            السنة المالية مقفلة
        </div>
    @endif

    <section class="period">
        <div class="period-box">
            <div class="label">السنة المالية</div>
            <div class="value">{{ $year->name }}</div>
        </div>
        <div class="period-box">
            <div class="label">الفترة</div>
            <div class="value">{{ $year->start_date->format('d/m/Y') }} — {{ $year->end_date->format('d/m/Y') }}</div>
        </div>
        <div class="period-box">
            <div class="label">تاريخ الطباعة</div>
            <div class="value">{{ $printedAt->format('d/m/Y H:i') }}</div>
        </div>
    </section>

    <section class="summary">
        <div class="card {{ $isProfit ? 'profit' : 'loss' }}">
            <div class="label">صافي نتيجة العام</div>
            <div class="value">
                <span class="amount">{{ $money(abs($preview['net_result'])) }}</span>
                <span>ج.م — {{ $isProfit ? 'ربح' : 'خسارة' }}</span>
            </div>
        </div>

        <div class="card">
            <div class="label">إجمالي مدين قيد الإقفال</div>
            <div class="value"><span class="amount">{{ $money($preview['total_debit']) }}</span><span>ج.م</span></div>
        </div>

        <div class="card">
            <div class="label">إجمالي دائن قيد الإقفال</div>
            <div class="value"><span class="amount">{{ $money($preview['total_credit']) }}</span><span>ج.م</span></div>
        </div>

        <div class="card {{ $balanced ? 'status-ok' : 'status-bad' }}">
            <div class="label">حالة القيد المتوقع</div>
            <div class="value">{{ $balanced ? 'متزن' : 'غير متزن' }}</div>
        </div>
    </section>

    <div class="section-title">قيد الإقفال المتوقع</div>

    <table>
        <thead>
            <tr>
                <th style="width:18%">كود الحساب</th>
                <th>الحساب</th>
                <th style="width:20%">مدين</th>
                <th style="width:20%">دائن</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($preview['lines'] as $line)
            <tr class="{{ (int) $line['account_id'] === (int) $preview['retained_account']->getKey() ? 'retained' : 'detail' }}">
                <td class="code">{{ $line['code'] }}</td>
                <td class="description">{{ $line['name'] }}</td>
                <td class="amount">{{ $display($line['debit']) }}</td>
                <td class="amount">{{ $display($line['credit']) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="padding:20px;text-align:center;color:var(--muted);">
                    لا توجد أرصدة إيرادات أو مصروفات تحتاج إلى إقفال.
                </td>
            </tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">الإجمالي</td>
                <td class="amount">{{ $money($preview['total_debit']) }}</td>
                <td class="amount">{{ $money($preview['total_credit']) }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($preview['unbalanced_entries']->isNotEmpty())
        <div class="warnings">
            يوجد {{ $preview['unbalanced_entries']->count() }} قيد غير متزن داخل الفترة، ولذلك لا يجوز تنفيذ الإقفال قبل المراجعة.
        </div>
    @endif

    <div class="note">
        حساب ترحيل نتيجة العام:
        <strong>{{ $preview['retained_account']->code }} — {{ $preview['retained_account']->name }}</strong>.
        هذا التقرير يعرض قيد الإقفال المتوقع وفق الحركات المسجلة حتى لحظة الطباعة، ولا يُعد إثباتًا لتنفيذ الإقفال طالما السنة المالية مفتوحة.
    </div>

    <section class="signatures">
        <div class="signature">إعداد الحسابات</div>
        <div class="signature">مراجعة المدير المالي</div>
        <div class="signature">الاعتماد</div>
    </section>

    <footer class="footer">
        <span>{{ $settings->commercialName() }}</span>
        <span>معاينة إقفال السنة المالية {{ $year->name }}</span>
        <span>{{ $year->isClosed() ? 'مقفلة' : 'مفتوحة — معاينة فقط' }}</span>
    </footer>
</main>
</body>
</html>
