<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>عرض سعر {{ $quotation->quotation_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
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

        .quotation-print {
            width: min(210mm, calc(100% - 32px));
            min-height: 277mm;
            margin: 24px auto;
            padding: 10mm;
            background: #fff;
            box-shadow: 0 6px 24px rgb(15 23 42 / 12%);
            font-size: 12.5px;
            line-height: 1.55;
        }

        .quotation-print .print-toolbar {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 12px;
        }

        .quotation-print .print-button {
            border: 0;
            border-radius: 6px;
            padding: 8px 16px;
            background: #2563eb;
            color: #fff;
            cursor: pointer;
            font: inherit;
        }

        .quotation-print .document-header {
            margin-bottom: 14px;
            padding: 14px 16px;
            border: 0;
            border-bottom: 2px solid #2563eb;
            border-radius: 0;
        }

        .quotation-print .company-document-title {
            margin-bottom: 7px;
            color: #1d4ed8;
            font-size: 24px;
            font-weight: 700;
            line-height: 1.2;
        }

        .quotation-print .company-document-details {
            min-width: 185px;
        }

        .quotation-print .company-document-logo {
            max-width: 125px;
            max-height: 68px;
        }

        .quotation-print .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 18px;
            margin: 14px 0;
            padding: 12px;
            border: 1px solid #cbd5e1;
        }

        .quotation-print .info-row {
            display: grid;
            grid-template-columns: 105px minmax(0, 1fr);
            gap: 8px;
        }

        .quotation-print .info-label {
            color: #64748b;
        }

        .quotation-print table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .quotation-print th,
        .quotation-print td {
            padding: 6px 5px;
            border: 1px solid #94a3b8;
            vertical-align: middle;
        }

        .quotation-print th {
            background: #eaf1fb;
            font-weight: 700;
            text-align: center;
        }

        .quotation-print .item-row {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .quotation-print .numeric,
        .quotation-print .item-code {
            direction: ltr;
            unicode-bidi: isolate;
            text-align: center;
            white-space: nowrap;
        }

        .quotation-print .description {
            overflow-wrap: anywhere;
            text-align: right;
        }

        .quotation-print .totals-wrap {
            display: flex;
            justify-content: flex-start;
            margin-top: 14px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .quotation-print .totals {
            width: min(100%, 360px);
            border: 1px solid #94a3b8;
        }

        .quotation-print .total-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 7px 10px;
            border-bottom: 1px solid #cbd5e1;
        }

        .quotation-print .total-row:last-child {
            border-bottom: 0;
        }

        .quotation-print .grand-total {
            background: #eaf1fb;
            font-size: 14px;
            font-weight: 700;
        }

        .quotation-print .text-block,
        .quotation-print .signatures,
        .quotation-print .footer {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .quotation-print .text-block {
            margin-top: 12px;
            padding: 10px;
            border: 1px solid #cbd5e1;
            white-space: pre-line;
        }

        .quotation-print .block-title {
            margin-bottom: 5px;
            font-weight: 700;
        }

        .quotation-print .signatures {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 50px;
            margin-top: 24px;
        }

        .quotation-print .signature {
            min-height: 75px;
            padding-top: 8px;
            border-top: 1px solid #475569;
            text-align: center;
        }

        .quotation-print .footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            text-align: center;
        }

        @media print {
            body {
                background: #fff;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .quotation-print {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .quotation-print .print-toolbar {
                display: none !important;
            }

            .quotation-print thead {
                display: table-header-group;
            }

            .quotation-print tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }

        @media screen and (max-width: 720px) {
            .quotation-print {
                width: 100%;
                margin: 0;
                padding: 16px;
                box-shadow: none;
            }

            .quotation-print .info-grid,
            .quotation-print .signatures {
                grid-template-columns: 1fr;
            }

            .quotation-print {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
<main class="quotation-print">
    <div class="print-toolbar">
        <button type="button" class="print-button" onclick="window.print()">طباعة</button>
    </div>

    <x-company-document-header
        class="document-header"
        :settings="$settings"
        document-title="عرض سعر"
        :document-number="$quotation->quotation_number"
        :document-date="$quotation->quotation_date->format('d/m/Y')"
        :expiry-date="$quotation->valid_until?->format('d/m/Y')"
    />

    <section class="info-grid" aria-label="بيانات العميل وعرض السعر">
        <div class="info-row"><span class="info-label">العميل</span><strong>{{ $quotation->customer->name }}</strong></div>
        @if (filled($quotation->customer->mobile ?? $quotation->customer->phone))
            <div class="info-row"><span class="info-label">رقم الهاتف</span><span class="numeric">{{ $quotation->customer->mobile ?? $quotation->customer->phone }}</span></div>
        @endif
        @if (filled($quotation->customer->address))
            <div class="info-row"><span class="info-label">العنوان</span><span>{{ $quotation->customer->address }}</span></div>
        @endif
        @if (filled($quotation->customer->tax_number))
            <div class="info-row"><span class="info-label">الرقم الضريبي</span><span class="numeric">{{ $quotation->customer->tax_number }}</span></div>
        @endif
    </section>

    <table aria-label="أصناف عرض السعر">
        <colgroup>
            <col style="width: 4%">
            <col style="width: 12%">
            <col style="width: 22%">
            <col style="width: 8%">
            <col style="width: 8%">
            <col style="width: 12%">
            <col style="width: 10%">
            <col style="width: 10%">
            <col style="width: 14%">
        </colgroup>
        <thead>
        <tr>
            <th>م</th>
            <th>كود الصنف</th>
            <th>بيان الصنف</th>
            <th>الوحدة</th>
            <th>الكمية</th>
            <th>سعر الوحدة</th>
            <th>الخصم</th>
            <th>الضريبة</th>
            <th>الإجمالي</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($quotation->items as $item)
            <tr class="item-row">
                <td class="numeric">{{ $loop->iteration }}</td>
                <td class="item-code">{{ $item->item->code }}</td>
                <td class="description">{{ $item->item->name }}</td>
                <td>{{ $item->unit->name }}</td>
                <td class="numeric">{{ \App\Support\QuantityFormatter::formatForDisplay($item->quantity) }}</td>
                <td class="numeric">{{ number_format((float) $item->unit_price, 2) }} ج.م</td>
                <td class="numeric">{{ number_format((float) $item->discount_amount, 2) }} ج.م</td>
                <td class="numeric">{{ number_format((float) $item->tax_amount, 2) }} ج.م</td>
                <td class="numeric"><strong>{{ number_format((float) $item->line_total, 2) }} ج.م</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals-wrap">
        <section class="totals" aria-label="إجماليات عرض السعر">
            <div class="total-row"><span>إجمالي الأصناف قبل الضريبة</span><span class="numeric">{{ number_format((float) $quotation->subtotal, 2) }} ج.م</span></div>
            <div class="total-row"><span>إجمالي الخصم</span><span class="numeric">{{ number_format((float) $quotation->discount_amount, 2) }} ج.م</span></div>
            <div class="total-row">
                <span>{{ $quotation->tax_type === \App\Enums\TaxType::Vat14 ? 'إجمالي ضريبة القيمة المضافة (14%)' : 'إجمالي الضريبة' }}</span>
                <span class="numeric">{{ number_format((float) $quotation->tax_amount, 2) }} ج.م</span>
            </div>
            <div class="total-row grand-total"><span>الإجمالي الكلي</span><span class="numeric">{{ number_format((float) $quotation->total_amount, 2) }} ج.م</span></div>
        </section>
    </div>

    @if (filled($quotation->terms_and_conditions))
        <section class="text-block">
            <div class="block-title">شروط العرض</div>
            <div>{{ $quotation->terms_and_conditions }}</div>
        </section>
    @endif

    @if (filled($quotation->notes))
        <section class="text-block">
            <div class="block-title">ملاحظات</div>
            <div>{{ $quotation->notes }}</div>
        </section>
    @endif

    <section class="signatures" aria-label="التوقيعات">
        <div class="signature">توقيع العميل</div>
        <div class="signature">اعتماد الشركة</div>
    </section>

    <footer class="footer">
        عرض سعر رقم <span class="numeric">{{ $quotation->quotation_number }}</span>
    </footer>
</main>
</body>
</html>
