<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>تقرير فواتير المبيعات</title>

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
            font-size: 10.5px;
        }

        th {
            background: #174b7d;
            color: #fff;
            border: 1px solid #ccd8e5;
            padding: 7px 4px;
            text-align: center;
        }

        td {
            border: 1px solid #d7e0e9;
            padding: 6px 4px;
            text-align: center;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .text {
            text-align: right;
        }

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
                <img
                    src="{{ asset('storage/' . $settings->logo_path) }}"
                    alt="OCTRAM"
                >
            @endif
        </div>

        <div class="compact-title">
            <h1>تقرير فواتير المبيعات</h1>

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

            <div>
                {{ $settings?->email ?: '' }}

                @if ($settings?->website)
                    - {{ $settings->website }}
                @endif
            </div>
        </div>

    </div>

    @php
        $totalInvoices = (float) $invoices->sum(
            fn ($invoice) => $invoice->totalAmount()
        );

        $totalTax = (float) $invoices->sum('tax_amount');

        $totalPaid = (float) $invoices->sum(
            fn ($invoice) => $invoice->paidAmount()
        );

        $totalRemaining = (float) $invoices->sum(
            fn ($invoice) => $invoice->remainingAmount()
        );
    @endphp

    <table>

        <thead>
        <tr>
            <th style="width:9%">رقم الفاتورة</th>
            <th style="width:10%">الرقم الإلكتروني</th>
            <th style="width:8%">التاريخ</th>
            <th style="width:17%">العميل</th>
            <th style="width:10%">أمر التوريد</th>
            <th style="width:10%">الإجمالي</th>
            <th style="width:8%">الضريبة</th>
            <th style="width:9%">المسدد</th>
            <th style="width:9%">المتبقي</th>
            <th style="width:8%">التعامل</th>
            <th style="width:8%">الاستحقاق</th>
            <th style="width:8%">الحالة</th>
            <th style="width:6%">أصناف</th>
        </tr>
        </thead>

        <tbody>

        @forelse ($invoices as $invoice)

            @php
                $electronicNumber =
                    data_get($invoice, 'electronic_invoice_number')
                    ?? data_get($invoice, 'electronic_number')
                    ?? data_get($invoice, 'e_invoice_number');
            @endphp

            <tr>

                <td>
                    {{ $invoice->document_number }}
                </td>

                <td>
                    {{ $electronicNumber ?: '—' }}
                </td>

                <td>
                    {{ $invoice->invoice_date?->format('d/m/Y') }}
                </td>

                <td class="text">
                    {{ $invoice->customer?->name ?: '—' }}
                </td>

                <td>
                    {{ $invoice->customerPurchaseOrder?->code ?: '—' }}
                </td>

                <td class="money">
                    {{ number_format(
                        (float) $invoice->totalAmount(),
                        2
                    ) }}
                </td>

                <td class="money">
                    {{ number_format(
                        (float) $invoice->tax_amount,
                        2
                    ) }}
                </td>

                <td class="money">
                    {{ number_format(
                        (float) $invoice->paidAmount(),
                        2
                    ) }}
                </td>

                <td class="money">
                    {{ number_format(
                        (float) $invoice->remainingAmount(),
                        2
                    ) }}
                </td>

                <td>
                    {{ $invoice->payment_type?->label() ?? '—' }}
                </td>

                <td>
                    {{ $invoice->due_date?->format('d/m/Y') ?: '—' }}
                </td>

                <td>
                    {{ $invoice->dueStatusLabel() }}
                </td>

                <td>
                    {{ $invoice->items_count }}
                </td>

            </tr>

        @empty

            <tr>
                <td colspan="13">
                    لا توجد فواتير مبيعات.
                </td>
            </tr>

        @endforelse

        @if ($invoices->isNotEmpty())

            <tr class="totals">

                <td colspan="5" class="text">
                    الإجماليات
                </td>

                <td class="money">
                    {{ number_format($totalInvoices, 2) }}
                </td>

                <td class="money">
                    {{ number_format($totalTax, 2) }}
                </td>

                <td class="money">
                    {{ number_format($totalPaid, 2) }}
                </td>

                <td class="money">
                    {{ number_format($totalRemaining, 2) }}
                </td>

                <td colspan="4"></td>

            </tr>

        @endif

        </tbody>

    </table>

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
