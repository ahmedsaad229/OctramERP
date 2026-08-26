<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تقرير عروض الأسعار</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        * { box-sizing: border-box; }

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
        }

        .toolbar {
            margin-bottom: 12px;
        }

        button {
            border: 0;
            border-radius: 6px;
            padding: 8px 18px;
            background: #174b7d;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
        }

        .compact-header {
            display: grid;
            grid-template-columns: 1fr 1.3fr 1fr;
            align-items: center;
            gap: 18px;
            padding: 10px 0 12px;
            margin-bottom: 12px;
            border-bottom: 2px solid #174b7d;
        }

        .compact-logo { text-align: left; }

        .compact-logo img {
            max-width: 190px;
            max-height: 85px;
            object-fit: contain;
        }

        .compact-title {
            text-align: center;
        }

        .compact-title h1 {
            margin: 0;
            color: #174b7d;
            font-size: 22px;
        }

        .compact-title .date {
            margin-top: 6px;
            font-size: 12px;
            color: #64748b;
        }

        .compact-info {
            font-size: 11px;
            line-height: 1.8;
        }

        .compact-info strong {
            color: #174b7d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9.5px;
        }

        th {
            background: #174b7d;
            color: #fff;
            border: 1px solid #ccd8e5;
            padding: 7px 3px;
            text-align: center;
        }

        td {
            border: 1px solid #d7e0e9;
            padding: 6px 3px;
            text-align: center;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .text { text-align: right; }

        .money {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
            font-weight: 700;
        }

        .totals td {
            background: #e7f0fa !important;
            font-weight: 900;
        }

        .footer {
            margin-top: 12px;
            text-align: center;
            font-size: 10px;
            color: #64748b;
        }

        @media print {
            body { background: #fff; }

            .report {
                width: 100%;
                margin: 0;
                padding: 0;
            }

            .toolbar { display: none; }
        }
    </style>
</head>

<body>

<main class="report">

    <div class="toolbar">
        <button onclick="window.print()">
            طباعة / حفظ PDF
        </button>
    </div>

    <div class="compact-header">

        <div class="compact-logo">
            @if (!empty($settings?->logo_path))
                <img
                    src="{{ asset('storage/' . $settings->logo_path) }}"
                    alt="OCTRAM"
                >
            @endif
        </div>

        <div class="compact-title">
            <h1>تقرير عروض الأسعار</h1>

            <div class="date">
                تاريخ التقرير:
                {{ $printedAt->format('d/m/Y') }}
            </div>
        </div>

        <div class="compact-info">
            <div>
                <strong>
                    {{ $settings?->company_name ?: 'OCTRAM' }}
                </strong>
            </div>

            <div>{{ $settings?->address ?: '' }}</div>

            <div>
                {{ $settings?->phone ?: '' }}
                @if ($settings?->mobile)
                    - {{ $settings->mobile }}
                @endif
            </div>

            <div>{{ $settings?->email ?: '' }}</div>
        </div>

    </div>

    @php
        $totalSubtotal = (float) $quotations->sum('subtotal');
        $totalDiscount = (float) $quotations->sum('discount_amount');
        $totalTax = (float) $quotations->sum('tax_amount');
        $grandTotal = (float) $quotations->sum('total_amount');
    @endphp

    <table>

        <thead>
        <tr>
            <th style="width:9%">رقم العرض</th>
            <th style="width:7%">التاريخ</th>
            <th style="width:7%">صالح حتى</th>
            <th style="width:16%">العميل</th>
            <th style="width:10%">مسؤول المبيعات</th>
            <th style="width:6%">الأصناف</th>
            <th style="width:9%">قبل الخصم</th>
            <th style="width:8%">الخصم</th>
            <th style="width:8%">الضريبة</th>
            <th style="width:10%">الإجمالي</th>
            <th style="width:10%">حالة التحويل</th>
            <th style="width:10%">الصلاحية</th>
        </tr>
        </thead>

        <tbody>

        @forelse ($quotations as $quotation)

            @php
                $conversionLabel = match ($quotation->conversionStatus()) {
                    'fully_converted' => 'تم التحويل بالكامل',
                    'partially_converted' => 'تحويل جزئي',
                    default => 'لم يتم التحويل',
                };
            @endphp

            <tr>

                <td>{{ $quotation->quotation_number }}</td>

                <td>
                    {{ $quotation->quotation_date?->format('d/m/Y') ?: '—' }}
                </td>

                <td>
                    {{ $quotation->valid_until?->format('d/m/Y') ?: '—' }}
                </td>

                <td class="text">
                    {{ $quotation->customer?->name ?: '—' }}
                </td>

                <td>
                    {{ $quotation->salesResponsible?->name ?: '—' }}
                </td>

                <td>{{ $quotation->items_count }}</td>

                <td class="money">
                    {{ number_format((float) $quotation->subtotal, 2) }}
                </td>

                <td class="money">
                    {{ number_format((float) $quotation->discount_amount, 2) }}
                </td>

                <td class="money">
                    {{ number_format((float) $quotation->tax_amount, 2) }}
                </td>

                <td class="money">
                    {{ number_format((float) $quotation->total_amount, 2) }}
                </td>

                <td>{{ $conversionLabel }}</td>

                <td>{{ $quotation->expiryLabel() }}</td>

            </tr>

        @empty

            <tr>
                <td colspan="12">
                    لا توجد عروض أسعار.
                </td>
            </tr>

        @endforelse

        @if ($quotations->isNotEmpty())

            <tr class="totals">

                <td colspan="6" class="text">
                    الإجماليات
                </td>

                <td class="money">
                    {{ number_format($totalSubtotal, 2) }}
                </td>

                <td class="money">
                    {{ number_format($totalDiscount, 2) }}
                </td>

                <td class="money">
                    {{ number_format($totalTax, 2) }}
                </td>

                <td class="money">
                    {{ number_format($grandTotal, 2) }}
                </td>

                <td colspan="2"></td>

            </tr>

        @endif

        </tbody>

    </table>

    <div class="footer">
        عدد عروض الأسعار:
        {{ $quotations->count() }}
        —
        تاريخ التقرير:
        {{ $printedAt->format('d/m/Y H:i') }}
    </div>

</main>

</body>
</html>
