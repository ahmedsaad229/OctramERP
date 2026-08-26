<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير أوكترام</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #fff;
            font-family: Tahoma, Arial, sans-serif;
            color: #172033;
            direction: rtl;
        }

        .page {
            width: 100%;
            max-width: 1300px;
            margin: 0 auto;
            padding: 20px;
        }

        .actions {
            text-align: center;
            margin-bottom: 15px;
        }

        .print-btn {
            border: 0;
            background: #174b7d;
            color: #fff;
            font-weight: 700;
            border-radius: 7px;
            padding: 9px 24px;
            cursor: pointer;
        }

        .report-title {
            background: #123f70;
            color: #fff;
            text-align: center;
            padding: 14px;
            border-radius: 8px;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 12px;
        }

        th {
            background: #174b7d;
            color: #fff;
            border: 1px solid #d5e0eb;
            padding: 9px 5px;
            font-weight: 800;
            text-align: center;
        }

        td {
            border: 1px solid #d5e0eb;
            padding: 8px 5px;
            vertical-align: middle;
            text-align: center;
            line-height: 1.5;
        }

        tbody tr:nth-child(even) td {
            background: #f8fbfe;
        }

        .numeric {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
            font-weight: 700;
        }

        .supplier,
        .item,
        .address {
            text-align: right;
        }

        .totals td {
            background: #eaf2fb !important;
            font-weight: 900;
        }

        .footer {
            margin-top: 14px;
            text-align: center;
            color: #7b8da2;
            font-size: 11px;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }

            .actions {
                display: none !important;
            }

            .page {
                max-width: none;
                padding: 0;
            }
        }
    </style>
</head>

<body>

<div class="page">

    <div class="actions">
        <button class="print-btn" onclick="window.print()">
            طباعة
        </button>
    </div>

    <div class="report-title">
        تقرير سجل أوكترام - المشتريات
    </div>

    @php
        $totalQuantity = (float) $entries->sum('purchase_quantity');

        $totalPurchaseValue = (float) $entries->sum(
            fn ($entry) =>
                (float) ($entry->purchase_quantity ?? 0)
                * (float) ($entry->purchase_price ?? 0)
        );

        $totalPurchaseTax =
            (float) $entries->sum('purchase_tax');

        $totalIncludingTax =
            (float) $entries->sum('purchase_price_including_tax');
    @endphp

    <table>

        <thead>
        <tr>
            <th style="width:9%">التاريخ</th>
            <th style="width:15%">الصنف</th>
            <th style="width:14%">المورد</th>
            <th style="width:18%">العنوان</th>
            <th style="width:11%">رقم التليفون</th>
            <th style="width:7%">الكمية</th>
            <th style="width:9%">سعر الوحدة</th>
            <th style="width:8%">الضريبة</th>
            <th style="width:9%">السعر شامل</th>
        </tr>
        </thead>

        <tbody>

        @forelse ($entries as $entry)

            <tr>

                <td>
                    {{ $entry->purchase_date?->format('d/m/Y') ?: '—' }}
                </td>

                <td class="item">
                    {{ $entry->purchaseItem?->name ?: '—' }}
                </td>

                <td class="supplier">
                    {{ $entry->supplier?->name ?: '—' }}
                </td>

                <td class="address">
                    {{ $entry->supplier_address ?: '—' }}
                </td>

                <td class="numeric">
                    {{ $entry->supplier_phone ?: '—' }}
                </td>

                <td class="numeric">
                    {{ number_format((float) ($entry->purchase_quantity ?? 0), 3) }}
                </td>

                <td class="numeric">
                    {{ number_format((float) ($entry->purchase_price ?? 0), 2) }}
                </td>

                <td class="numeric">
                    {{ number_format((float) ($entry->purchase_tax ?? 0), 2) }}
                </td>

                <td class="numeric">
                    {{ number_format((float) ($entry->purchase_price_including_tax ?? 0), 2) }}
                </td>

            </tr>

        @empty

            <tr>
                <td colspan="9">
                    لا توجد بيانات مسجلة.
                </td>
            </tr>

        @endforelse

        @if ($entries->isNotEmpty())

            <tr class="totals">

                <td colspan="5" style="text-align:right">
                    الإجماليات
                </td>

                <td class="numeric">
                    {{ number_format($totalQuantity, 3) }}
                </td>

                <td class="numeric">
                    {{ number_format($totalPurchaseValue, 2) }}
                </td>

                <td class="numeric">
                    {{ number_format($totalPurchaseTax, 2) }}
                </td>

                <td class="numeric">
                    {{ number_format($totalIncludingTax, 2) }}
                </td>

            </tr>

        @endif

        </tbody>

    </table>

    <div class="footer">
        تاريخ الطباعة:
        {{ $printedAt?->format('d/m/Y H:i') }}
    </div>

</div>

</body>
</html>
