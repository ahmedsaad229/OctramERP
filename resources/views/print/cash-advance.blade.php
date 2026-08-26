<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>عهدة نقدية {{ $advance->document_number }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef1f5;
            color: #1f2937;
            font-family: Arial, Tahoma, sans-serif;
        }

        .page {
            width: min(285mm, calc(100% - 20px));
            margin: 10px auto;
            padding: 9mm;
            background: #fff;
            border: 1px solid #dbe3ec;
            border-radius: 8px;
        }

        .toolbar {
            text-align: left;
            margin-bottom: 10px;
        }

        .print-btn {
            border: 0;
            border-radius: 6px;
            padding: 8px 18px;
            background: #15477f;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .top {
            display: grid;
            grid-template-columns: 1fr 1.2fr 1fr;
            align-items: start;
            gap: 18px;
            padding-bottom: 14px;
            border-bottom: 2px solid #c92d31;
        }

        .company-logo {
            text-align: left;
        }

        .company-logo img {
            max-width: 280px;
            max-height: 125px;
            object-fit: contain;
        }

        .doc-center {
            text-align: center;
        }

        .doc-title {
            color: #184b86;
            font-size: 25px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .doc-number {
            display: inline-block;
            padding: 7px 20px;
            border-radius: 5px;
            background: #174d8d;
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            direction: ltr;
        }

        .top-right {
            font-size: 14px;
            line-height: 1.9;
        }

        .top-right strong {
            color: #172033;
        }

        .company-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            padding: 12px 0 14px;
            border-bottom: 1px solid #dbe3ec;
            font-size: 13px;
            line-height: 1.9;
        }

        .company-info .left {
            direction: ltr;
            text-align: left;
        }

        .section-label {
            display: inline-block;
            margin-top: 12px;
            padding: 7px 16px;
            background: #174d8d;
            color: #fff;
            border-radius: 5px;
            font-weight: 800;
            font-size: 14px;
        }

        .advance-details {
            margin-top: 4px;
            border-top: 1px solid #dbe3ec;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 170px 15px 1fr;
            gap: 8px;
            padding: 8px 6px;
            border-bottom: 1px solid #dbe3ec;
            font-size: 13px;
        }

        .detail-label {
            font-weight: 800;
        }

        .detail-value {
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            background: #55b2d8;
            color: #fff;
            font-weight: 800;
        }

        .table-title {
            display: inline-block;
            margin-top: 10px;
            margin-bottom: 0;
            padding: 7px 18px;
            background: #174d8d;
            color: #fff;
            border-radius: 5px 5px 0 0;
            font-weight: 800;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 12px;
        }

        th {
            background: #174d8d;
            color: #fff;
            border: 1px solid #afc3db;
            padding: 7px 5px;
            font-weight: 800;
            text-align: center;
        }

        td {
            border: 1px solid #d5dee8;
            padding: 7px 5px;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .center {
            text-align: center;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
            text-align: center;
        }

        .description {
            text-align: right;
        }

        .totals-wrap {
            display: flex;
            justify-content: flex-start;
            margin-top: 10px;
        }

        .totals {
            width: 420px;
            border: 1px solid #b8c8da;
            border-radius: 5px;
            overflow: hidden;
        }

        .total-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 38px;
            border-bottom: 1px dotted #9fb1c5;
        }

        .total-row:last-child {
            border-bottom: 0;
        }

        .total-label,
        .total-value {
            padding: 8px 10px;
            font-size: 13px;
        }

        .total-label {
            font-weight: 800;
        }

        .total-value {
            direction: ltr;
            unicode-bidi: isolate;
            font-weight: 800;
            text-align: center;
            border-right: 1px solid #d4deea;
        }

        .grand {
            background: #e7f0fb;
            color: #123f70;
            font-size: 14px;
            font-weight: 900;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 70px;
            margin-top: 24px;
            text-align: center;
            color: #174d8d;
            font-weight: 800;
        }

        .signature-line {
            display: block;
            width: 150px;
            margin: 24px auto 0;
            border-bottom: 2px dotted #174d8d;
        }

        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #174d8d;
            text-align: center;
            color: #4b5563;
            font-size: 10px;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                width: 100%;
                margin: 0;
                padding: 0;
                border: 0;
            }

            .toolbar {
                display: none;
            }
        }
    </style>
</head>

<body>

<main class="page">

    <div class="toolbar">
        <button class="print-btn" type="button" onclick="window.print()">
            طباعة / حفظ PDF
        </button>
    </div>

    <div class="top">

        <div class="company-logo">
            @if (!empty($settings?->logo_path))
                <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="OCTRAM">
            @endif
        </div>

        <div class="doc-center">
            <div class="doc-title">عهدة نقدية</div>

            <div class="doc-number">
                رقم المستند : {{ $advance->document_number }}
            </div>
        </div>

        <div class="top-right">
            <div>
                <strong>التاريخ :</strong>
                {{ $advance->advance_date?->format('d/m/Y') ?: '—' }}
            </div>

            <div>
                <strong>التسوية المتوقعة :</strong>
                {{ $advance->due_date?->format('d/m/Y') ?: '—' }}
            </div>

            <div>
                <strong>سجل تجاري :</strong>
                {{ $settings?->commercial_register_number ?: '—' }}
            </div>

            <div>
                <strong>رقم ضريبي :</strong>
                {{ $settings?->tax_number ?: '—' }}
            </div>
        </div>

    </div>

    <div class="company-info">

        <div>
            <div>{{ $settings?->address ?: '—' }}</div>
        </div>

        <div class="left">
            <div>
                {{ $settings?->phone ?: '' }}
                @if ($settings?->mobile)
                    - {{ $settings->mobile }}
                @endif
            </div>

            <div>
                {{ $settings?->email ?: '' }}
                @if ($settings?->website)
                    - {{ $settings->website }}
                @endif
            </div>
        </div>

    </div>

    <div class="section-label">
        بيانات العهدة
    </div>

    <div class="advance-details">

        <div class="detail-row">
            <div class="detail-label">مستلم العهدة</div>
            <div>:</div>
            <div class="detail-value">{{ $advance->recipient_name }}</div>
        </div>

        <div class="detail-row">
            <div class="detail-label">مبلغ العهدة</div>
            <div>:</div>
            <div class="detail-value ltr">
                {{ number_format((float) $advance->amount, 2) }} ج.م
            </div>
        </div>

        <div class="detail-row">
            <div class="detail-label">الغرض من العهدة</div>
            <div>:</div>
            <div class="detail-value">{{ $advance->purpose ?: '—' }}</div>
        </div>

        <div class="detail-row">
            <div class="detail-label">الحالة</div>
            <div>:</div>
            <div class="detail-value">
                <span class="status-badge">
                    {{ \App\Models\CashAdvance::statusOptions()[$advance->status] ?? $advance->status }}
                </span>
            </div>
        </div>

        <div class="detail-row">
            <div class="detail-label">ملاحظات</div>
            <div>:</div>
            <div class="detail-value">{{ $advance->notes ?: '—' }}</div>
        </div>

    </div>

    <div class="table-title">
        حركات التسوية
    </div>

    <table>
        <thead>
        <tr>
            <th style="width:5%">#</th>
            <th style="width:14%">التاريخ</th>
            <th style="width:12%">نوع الحركة</th>
            <th style="width:38%">البيان</th>
            <th style="width:18%">رقم المستند / الفاتورة</th>
            <th style="width:13%">المبلغ</th>
        </tr>
        </thead>

        <tbody>
        @forelse ($advance->settlements as $index => $line)
            <tr>
                <td class="center">{{ $index + 1 }}</td>

                <td class="center">
                    {{ $line->settlement_date?->format('d/m/Y') ?: '—' }}
                </td>

                <td class="center">
                    {{ \App\Models\CashAdvanceSettlement::typeOptions()[$line->type] ?? $line->type }}
                </td>

                <td class="description">
                    {{ $line->description ?: '—' }}
                </td>

                <td class="ltr">
                    {{ $line->document_number ?: '—' }}
                </td>

                <td class="ltr">
                    {{ number_format((float) $line->amount, 2) }} ج.م
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="center">
                    لا توجد حركات تسوية حتى الآن
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="totals-wrap">
        <div class="totals">

            <div class="total-row">
                <div class="total-label">مبلغ العهدة</div>
                <div class="total-value">
                    {{ number_format((float) $advance->amount, 2) }} ج.م
                </div>
            </div>

            <div class="total-row">
                <div class="total-label">إجمالي المصروف</div>
                <div class="total-value">
                    {{ number_format((float) $advance->total_spent, 2) }} ج.م
                </div>
            </div>

            <div class="total-row">
                <div class="total-label">إجمالي المرتجع</div>
                <div class="total-value">
                    {{ number_format((float) $advance->total_returned, 2) }} ج.م
                </div>
            </div>

            <div class="total-row grand">
                <div class="total-label grand">المتبقي</div>
                <div class="total-value grand">
                    {{ number_format((float) $advance->remaining_amount, 2) }} ج.م
                </div>
            </div>

        </div>
    </div>

    <div class="signatures">
        <div>
            مستلم العهدة
            <span class="signature-line"></span>
        </div>

        <div>
            المراجعة
            <span class="signature-line"></span>
        </div>

        <div>
            الاعتماد
            <span class="signature-line"></span>
        </div>
    </div>

    <div class="footer">
        تاريخ الطباعة:
        {{ $printedAt?->format('d/m/Y H:i') }}
    </div>

</main>

</body>
</html>
