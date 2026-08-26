<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">

    <title>متابعة بيع أصناف المشتريات</title>

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
            font-weight: 700;
            cursor: pointer;
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

        .compact-logo {
            text-align: left;
        }

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
            font-size: 21px;
        }

        .compact-title .date {
            margin-top: 5px;
            font-size: 11px;
            color: #64748b;
        }

        .compact-info {
            font-size: 11px;
            line-height: 1.8;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 7px;
            margin-bottom: 12px;
        }

        .summary-card {
            border: 1px solid #d7e0e9;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }

        .summary-label {
            font-size: 9px;
            color: #64748b;
        }

        .summary-value {
            margin-top: 3px;
            font-size: 13px;
            font-weight: 900;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9px;
        }

        th {
            background: #174b7d;
            color: #fff;
            padding: 6px 3px;
            border: 1px solid #c8d4e1;
            text-align: center;
        }

        td {
            padding: 5px 3px;
            border: 1px solid #d7e0e9;
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
            text-align: center;
            white-space: nowrap;
        }

        .item {
            text-align: right;
            line-height: 1.4;
        }

        .customers {
            font-size: 8.5px;
            line-height: 1.5;
        }

        .status {
            font-weight: 800;
            text-align: center;
        }

        .details-row td {
            background: #f5f8fc !important;
        }

        .sales-detail {
            width: 100%;
            font-size: 8px;
            margin-top: 3px;
        }

        .sales-detail th {
            background: #e8eef6;
            color: #1f2937;
        }

        .footer {
            margin-top: 10px;
            text-align: center;
            color: #64748b;
            font-size: 9px;
        }

        @media print {
            body {
                background: #fff;
            }

            .report {
                width: 100%;
                margin: 0;
                padding: 0;
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
            <h1>متابعة بيع أصناف المشتريات</h1>

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

            <div>
                {{ $settings?->address ?: '' }}
            </div>

            <div>
                {{ $settings?->phone ?: '' }}

                @if ($settings?->mobile)
                    - {{ $settings->mobile }}
                @endif
            </div>

            <div>
                {{ $settings?->email ?: '' }}
            </div>
        </div>

    </div>

    @php
        $summary = $report['summary'] ?? [];
        $rows = $report['rows'] ?? [];
    @endphp

    <div class="summary">

        <div class="summary-card">
            <div class="summary-label">كمية الشراء</div>
            <div class="summary-value">
                {{ number_format($summary['purchase_quantity'] ?? 0, 2) }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">المباع</div>
            <div class="summary-value">
                {{ number_format($summary['sold_quantity'] ?? 0, 2) }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">غير المباع</div>
            <div class="summary-value">
                {{ number_format($summary['remaining_quantity'] ?? 0, 2) }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">بيع كامل</div>
            <div class="summary-value">
                {{ $summary['fully_sold'] ?? 0 }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">بيع جزئي</div>
            <div class="summary-value">
                {{ $summary['partially_sold'] ?? 0 }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">لم يتم البيع</div>
            <div class="summary-value">
                {{ $summary['not_sold'] ?? 0 }}
            </div>
        </div>

    </div>

    <table>
        <thead>
        <tr>
            <th style="width:8%">فاتورة الشراء</th>
            <th style="width:7%">التاريخ</th>
            <th style="width:13%">المورد</th>
            <th style="width:8%">كود الصنف</th>
            <th style="width:25%">الصنف</th>
            <th style="width:7%">كمية الشراء</th>
            <th style="width:6%">المباع</th>
            <th style="width:6%">المتبقي</th>
            <th style="width:9%">الحالة</th>
            <th style="width:11%">العملاء</th>
        </tr>
        </thead>

        <tbody>

        @forelse ($rows as $row)

            <tr>
                <td class="ltr">
                    {{ $row['purchase_document'] }}
                </td>

                <td class="center">
                    {{ $row['purchase_date'] }}
                </td>

                <td>
                    {{ $row['supplier'] }}
                </td>

                <td class="ltr">
                    {{ $row['item_code'] }}
                </td>

                <td class="item">
                    {{ $row['item_name'] }}
                </td>

                <td class="ltr">
                    {{ number_format($row['purchase_quantity'], 2) }}
                </td>

                <td class="ltr">
                    {{ number_format($row['sold_quantity'], 2) }}
                </td>

                <td class="ltr">
                    {{ number_format($row['remaining_quantity'], 2) }}
                </td>

                <td class="status">
                    {{ $row['status_label'] }}
                </td>

                <td class="customers">
                    @forelse ($row['customers'] as $customer)
                        <div>{{ $customer }}</div>
                    @empty
                        —
                    @endforelse
                </td>
            </tr>

            @if (count($row['allocations']))
                <tr class="details-row">
                    <td colspan="10">

                        <table class="sales-detail">
                            <thead>
                            <tr>
                                <th>فاتورة البيع</th>
                                <th>تاريخ البيع</th>
                                <th>العميل</th>
                                <th>الكمية</th>
                                <th>سعر البيع</th>
                            </tr>
                            </thead>

                            <tbody>

                            @foreach ($row['allocations'] as $sale)

                                <tr>
                                    <td class="ltr">
                                        {{ $sale['document_number'] ?: '—' }}
                                    </td>

                                    <td class="center">
                                        {{ $sale['invoice_date'] ?: '—' }}
                                    </td>

                                    <td>
                                        {{ $sale['customer'] }}
                                    </td>

                                    <td class="ltr">
                                        {{ number_format($sale['quantity'], 2) }}
                                    </td>

                                    <td class="ltr">
                                        {{ number_format($sale['unit_price'], 2) }}
                                    </td>
                                </tr>

                            @endforeach

                            </tbody>
                        </table>

                    </td>
                </tr>
            @endif

        @empty

            <tr>
                <td colspan="10" class="center">
                    لا توجد بيانات مطابقة.
                </td>
            </tr>

        @endforelse

        </tbody>
    </table>

    <div class="footer">
        تاريخ الطباعة:
        {{ $printedAt->format('d/m/Y H:i') }}
    </div>

</main>

</body>
</html>
