<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إذن تسليم - {{ $quotation->quotation_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        :root {
            --primary:#123b67;
            --primary-dark:#0c2f55;
            --soft:#f2f7fc;
            --border:#bfd0e2;
            --text:#1f2937;
            --muted:#64748b;
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            background:#eef2f7;
            color:var(--text);
            font-family:Arial,Tahoma,sans-serif;
            line-height:1.55;
        }
        .page {
            width:min(210mm,calc(100% - 24px));
            min-height:277mm;
            margin:18px auto;
            padding:10mm;
            background:#fff;
            box-shadow:0 8px 28px rgb(15 23 42 / 12%);
        }
        .toolbar { margin-bottom:12px; }
        .print-button {
            border:0; border-radius:7px; padding:9px 18px;
            background:#2563eb; color:#fff; cursor:pointer; font-weight:800;
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
            grid-template-columns:minmax(150px,.75fr) minmax(280px,1.45fr) minmax(130px,.65fr)!important;
            align-items:center!important; gap:16px!important; direction:rtl!important;
        }
        .company-document-title {
            display:flex!important; align-items:center!important; justify-content:center!important;
            min-height:44px!important; margin-bottom:8px!important; padding:8px 14px!important;
            border-radius:8px!important; background:var(--primary)!important;
            color:#fff!important; font-size:18px!important; font-weight:800!important;
        }
        .company-document-company { text-align:center!important; }
        .company-document-company-name {
            color:var(--primary)!important; font-size:18px!important;
            font-weight:800!important; white-space:nowrap!important;
        }
        .company-document-logo-wrap {
            display:flex!important; justify-content:flex-start!important; align-items:center!important;
            width:100%!important; direction:ltr!important;
        }
        .company-document-logo { max-width:130px!important; max-height:76px!important; object-fit:contain!important; }

        .reference {
            margin:0 0 12px;
            padding:7px 12px;
            border:1px solid #d7e3ef;
            border-radius:7px;
            background:#f8fbfe;
            color:var(--muted);
            text-align:center;
            font-size:10px;
        }
        .info-grid {
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:8px;
            margin:12px 0;
        }
        .info {
            min-height:62px;
            padding:9px 11px;
            border:1px solid var(--border);
            border-radius:8px;
            background:#fff;
        }
        .info .label { color:var(--muted); font-size:9px; }
        .info .value { margin-top:4px; color:var(--primary-dark); font-weight:800; }
        .ltr { direction:ltr; unicode-bidi:isolate; }

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
            padding:8px 7px;
            border-left:1px solid var(--border);
            border-bottom:1px solid var(--border);
            vertical-align:middle;
        }
        th:last-child,td:last-child { border-left:0; }
        th { background:var(--primary); color:#fff; font-weight:800; text-align:center; }
        tbody tr:nth-child(even) td { background:#f8fbfe; }
        tr { break-inside:avoid; page-break-inside:avoid; }
        .center { text-align:center; }
        .item-name { font-weight:700; }
        .notes {
            margin-top:12px;
            padding:10px 12px;
            border:1px solid var(--border);
            border-radius:8px;
            background:#fbfdff;
        }
        .notes .title { color:var(--primary-dark); font-weight:800; margin-bottom:4px; }
        .signatures {
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:22px;
            margin-top:32px;
            text-align:center;
            color:var(--muted);
            font-size:9px;
        }
        .signature { padding-top:28px; border-top:1px solid #64748b; }
        .footer {
            display:flex; justify-content:space-between; gap:12px;
            margin-top:22px; padding-top:9px; border-top:1px solid var(--border);
            color:var(--muted); font-size:9px;
        }
        @media print {
            body { background:#fff; print-color-adjust:exact; -webkit-print-color-adjust:exact; }
            .page { width:auto; min-height:0; margin:0; padding:0; box-shadow:none; }
            .toolbar { display:none!important; }
        }
    </style>
    @include('print.partials.report-style')
</head>
<body>
@php
    $customer = $quotation->customer;
    $salesResponsible = $quotation->salesResponsible;
@endphp
<main class="page">
    <div class="toolbar">
        <button class="print-button" onclick="window.print()">طباعة / حفظ PDF</button>
    </div>

    <x-company-document-header
        class="company-document-header"
        :settings="$settings"
        document-title="إذن تسليم"
        :document-number="$quotation->quotation_number"
        :document-date="$quotation->quotation_date?->format('d/m/Y')"
    />

    <div class="reference">
        مرجع المستند: عرض السعر رقم
        <strong class="ltr">{{ $quotation->quotation_number }}</strong>
        — هذا المستند لا يعرض أي أسعار أو قيم مالية.
    </div>

    <section class="info-grid">
        <div class="info">
            <div class="label">العميل</div>
            <div class="value">{{ $customer?->name ?? '—' }}</div>
        </div>
        <div class="info">
            <div class="label">كود العميل</div>
            <div class="value ltr">{{ $customer?->code ?? '—' }}</div>
        </div>
        <div class="info">
            <div class="label">التاريخ</div>
            <div class="value ltr">{{ $quotation->quotation_date?->format('d/m/Y') ?? '—' }}</div>
        </div>

        <div class="info">
            <div class="label">المخزن المرجعي</div>
            <div class="value">{{ $quotation->warehouse?->name ?? '—' }}</div>
        </div>
        <div class="info">
            <div class="label">مسؤول المبيعات</div>
            <div class="value">{{ $salesResponsible?->name ?? '—' }}</div>
        </div>
        <div class="info">
            <div class="label">أُنشئ بواسطة</div>
            <div class="value">{{ $quotation->creator?->name ?? '—' }}</div>
        </div>

        <div class="info">
            <div class="label">هاتف العميل</div>
            <div class="value ltr">{{ $customer?->mobile ?? $customer?->phone ?? '—' }}</div>
        </div>
        <div class="info">
            <div class="label">مسؤول العميل</div>
            <div class="value">{{ $customer?->contact_person ?? '—' }}</div>
        </div>
        <div class="info">
            <div class="label">العنوان</div>
            <div class="value">{{ $customer?->address ?? '—' }}</div>
        </div>
    </section>

    <div class="section-title">الأصناف المطلوب تسليمها</div>

    <table>
        <thead>
            <tr>
                <th style="width:7%">م</th>
                <th style="width:16%">كود الصنف</th>
                <th>بيان الصنف</th>
                <th style="width:14%">الوحدة</th>
                <th style="width:14%">الكمية</th>
                <th style="width:20%">ملاحظات</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($quotation->items as $index => $line)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td class="center ltr">{{ $line->item?->code ?? '—' }}</td>
                <td class="item-name">{{ $line->item?->name ?? '—' }}</td>
                <td class="center">{{ $line->unit?->name ?? '—' }}</td>
                <td class="center ltr">{{ \App\Support\QuantityFormatter::formatForDisplay($line->quantity) }}</td>
                <td>{{ $line->notes ?: '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="center" style="padding:22px;color:var(--muted);">لا توجد أصناف.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    @if (filled($quotation->notes))
        <section class="notes">
            <div class="title">ملاحظات</div>
            <div>{{ $quotation->notes }}</div>
        </section>
    @endif

    <section class="signatures">
        <div class="signature">مسؤول التسليم</div>
        <div class="signature">المستلم</div>
        <div class="signature">اعتماد الشركة</div>
    </section>

    <footer class="footer">
        <span>{{ $settings->commercialName() }}</span>
        <span>إذن تسليم مرتبط بعرض السعر <span class="ltr">{{ $quotation->quotation_number }}</span></span>
        <span>بدون أسعار</span>
    </footer>
</main>
</body>
</html>
