<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>التقرير التفصيلي لفواتير المشتريات</title>

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
            background: #eef0f3;
            color: #1f2937;
            font-family: Arial, Tahoma, sans-serif;
        }

        .report {
            width: min(285mm, calc(100% - 24px));
            margin: 15px auto;
            padding: 10mm;
            background: #fff;
            box-shadow: 0 5px 20px rgb(15 23 42 / 10%);
        }

        .toolbar {
            margin-bottom: 12px;
        }

        button {
            border: 0;
            border-radius: 6px;
            padding: 8px 18px;
            background: #174b7d;
            color: white;
            cursor: pointer;
            font-weight: 700;
        }

        .compact-header {
            display: grid;
            grid-template-columns: 1fr 1.3fr 1fr;
            align-items: center;
            gap: 18px;
            padding: 10px 0 12px;
            margin-bottom: 14px;
            border-bottom: 2px solid #174b7d;
        }

        .compact-logo { text-align: left; }
        .compact-logo img { max-width: 190px; max-height: 85px; object-fit: contain; }
        .compact-title { text-align: center; }
        .compact-title h1 { margin: 0; color: #174b7d; font-size: 22px; }
        .compact-title .date { margin-top: 6px; font-size: 12px; color: #64748b; }
        .compact-info { font-size: 11px; line-height: 1.8; }
        .compact-info strong { color: #174b7d; }

        .invoice-block {
            margin-bottom: 24px;
            page-break-inside: avoid;
            border: 1px solid #cbd8e5;
            border-radius: 7px;
            overflow: hidden;
        }

        .invoice-head {
            background: #edf4fb;
            padding: 10px 12px;
            border-bottom: 1px solid #cbd8e5;
        }

        .invoice-title {
            color: #174b7d;
            font-size: 16px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .invoice-info {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px 16px;
            font-size: 11px;
        }

        .info-label {
            color: #64748b;
            font-weight: 700;
        }

        .info-value {
            font-weight: 800;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10.5px;
        }

        th {
            background: #174b7d;
            color: #fff;
            padding: 7px 4px;
            border: 1px solid #c4d2e0;
            text-align: center;
        }

        td {
            padding: 6px 4px;
            border: 1px solid #d7e0e9;
            vertical-align: middle;
            text-align: center;
        }

        tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .text {
            text-align: right;
        }

        .money,
        .qty {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
            font-weight: 700;
        }

        .invoice-totals {
            width: 430px;
            margin: 10px 10px 10px auto;
            border: 1px solid #bfd0e1;
        }

        .total-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 1px solid #d8e1ea;
        }

        .total-row:last-child {
            border-bottom: 0;
        }

        .total-label,
        .total-value {
            padding: 7px 10px;
            font-size: 11px;
        }

        .total-label {
            font-weight: 800;
            background: #f8fafc;
        }

        .total-value {
            font-weight: 900;
            direction: ltr;
            unicode-bidi: isolate;
            text-align: center;
        }

        .grand {
            background: #e8f1fb;
            color: #123f70;
            font-size: 12px;
        }

        .footer {
            margin-top: 14px;
            text-align: center;
            color: #64748b;
            font-size: 10px;
        }

        @media print {
            body {
                background: #fff;
            }

            .report {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .toolbar {
                display: none;
            }
        }
    </style>
</head>

<body>

<main class="report">

    <div class="toolbar">
        <button type="button" onclick="window.print()">
            طباعة / حفظ PDF
        </button>
    </div>

    <div class="compact-header">

        <div class="compact-logo">
            @if (!empty($settings?->logo_path))
                <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="OCTRAM">
            @endif
        </div>

        <div class="compact-title">
            <h1>التقرير التفصيلي لفواتير المشتريات</h1>
            <div class="date">
                تاريخ التقرير: {{ $printedAt->format('d/m/Y') }}
            </div>
        </div>

        <div class="compact-info">
            <div><strong>{{ $settings?->company_name ?: 'OCTRAM' }}</strong></div>
            <div>{{ $settings?->address ?: '' }}</div>
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

    @forelse ($invoices as $invoice)

        @php
            $subtotal = (float) $invoice->items->sum(
                fn ($item) =>
                    (float) $item->quantity
                    * (float) $item->unit_cost
            );
        @endphp

        <section class="invoice-block">

            <div class="invoice-head">

                <div class="invoice-title">
                    فاتورة شراء: {{ $invoice->code }}
                </div>

                <div class="invoice-info">

                    <div>
                        <span class="info-label">التاريخ:</span>
                        <span class="info-value">
                            {{ $invoice->invoice_date?->format('d/m/Y') ?: '—' }}
                        </span>
                    </div>

                    <div>
                        <span class="info-label">رقم فاتورة المورد:</span>
                        <span class="info-value">
                            {{ $invoice->invoice_number ?: '—' }}
                        </span>
                    </div>

                    <div>
                        <span class="info-label">المورد:</span>
                        <span class="info-value">
                            {{ $invoice->supplier?->name ?: '—' }}
                        </span>
                    </div>

                    <div>
                        <span class="info-label">المخزن:</span>
                        <span class="info-value">
                            {{ $invoice->warehouse?->name ?: '—' }}
                        </span>
                    </div>

                    <div>
                        <span class="info-label">أمر التوريد:</span>
                        <span class="info-value">
                            {{ $invoice->supplierPurchaseOrder?->code ?: '—' }}
                        </span>
                    </div>

                    <div>
                        <span class="info-label">طلب الشراء:</span>
                        <span class="info-value">
                            {{ $invoice->supplierPurchaseOrder?->purchaseRequest?->code ?: '—' }}
                        </span>
                    </div>

                    <div>
                        <span class="info-label">نوع التعامل:</span>
                        <span class="info-value">
                            {{ $invoice->payment_type?->label() ?? '—' }}
                        </span>
                    </div>

                    <div>
                        <span class="info-label">تاريخ الاستحقاق:</span>
                        <span class="info-value">
                            {{ $invoice->due_date?->format('d/m/Y') ?: '—' }}
                        </span>
                    </div>

                </div>

            </div>

            <table>

                <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:11%">كود الصنف</th>
                    <th style="width:28%">اسم الصنف</th>
                    <th style="width:10%">الكمية</th>
                    <th style="width:12%">سعر الوحدة</th>
                    <th style="width:10%">الضريبة</th>
                    <th style="width:12%">إجمالي السطر</th>
                    <th style="width:12%">ملاحظات</th>
                </tr>
                </thead>

                <tbody>

                @forelse ($invoice->items as $index => $line)

                    <tr>

                        <td>{{ $index + 1 }}</td>

                        <td>
                            {{ $line->item?->code ?: '—' }}
                        </td>

                        <td class="text">
                            {{ $line->item?->name ?: '—' }}
                        </td>

                        <td class="qty">
                            {{ number_format((float) $line->quantity, 2) }}
                        </td>

                        <td class="money">
                            {{ number_format((float) $line->unit_cost, 2) }}
                        </td>

                        <td class="money">
                            {{ number_format((float) $line->tax_amount, 2) }}
                        </td>

                        <td class="money">
                            {{ number_format((float) $line->total_cost, 2) }}
                        </td>

                        <td class="text">
                            {{ $line->notes ?: '—' }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="8">
                            لا توجد أصناف داخل الفاتورة.
                        </td>
                    </tr>

                @endforelse

                </tbody>
            </table>

            <div class="invoice-totals">

                <div class="total-row">
                    <div class="total-label">
                        إجمالي البنود قبل الخصم
                    </div>

                    <div class="total-value">
                        {{ number_format($subtotal, 2) }}
                    </div>
                </div>

                <div class="total-row">
                    <div class="total-label">
                        الخصم
                    </div>

                    <div class="total-value">
                        {{ number_format((float) ($invoice->discount_amount ?? 0), 2) }}
                    </div>
                </div>

                <div class="total-row">
                    <div class="total-label">
                        الضريبة
                    </div>

                    <div class="total-value">
                        {{ number_format((float) ($invoice->tax_amount ?? 0), 2) }}
                    </div>
                </div>

                <div class="total-row grand">
                    <div class="total-label grand">
                        الإجمالي النهائي
                    </div>

                    <div class="total-value grand">
                        {{ number_format((float) $invoice->totalAmount(), 2) }}
                    </div>
                </div>

            </div>

        </section>

    @empty

        <div style="text-align:center;padding:30px">
            لا توجد فواتير مشتريات.
        </div>

    @endforelse

    <div class="footer">
        عدد الفواتير:
        {{ $invoices->count() }}
        —
        تاريخ التقرير:
        {{ $printedAt->format('d/m/Y H:i') }}
    </div>

</main>

</body>
</html>
