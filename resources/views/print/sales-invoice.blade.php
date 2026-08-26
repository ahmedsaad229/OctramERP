<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة مبيعات {{ $invoice->document_number }}</title>
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
    @include('print.partials.report-style')
    <style>
        /* SALES-INVOICE-TOTALS-FIX */

        .quotation-print .totals-wrap,
        .invoice-print .totals-wrap,
        .sales-invoice-print .totals-wrap {
            display: flex !important;
            justify-content: flex-start !important;
            align-items: flex-start !important;
            margin-top: 16px !important;
        }

        .quotation-print .totals-wrap .totals,
        .invoice-print .totals-wrap .totals,
        .sales-invoice-print .totals-wrap .totals {
            display: block !important;
            width: min(100%, 390px) !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 1px solid #b8c7d9 !important;
            border-radius: 8px !important;
            overflow: hidden !important;
            background: #ffffff !important;
            box-shadow: none !important;
        }

        .quotation-print .totals .total-row,
        .invoice-print .totals .total-row,
        .sales-invoice-print .totals .total-row {
            display: grid !important;
            grid-template-columns: minmax(160px, 1fr) minmax(125px, auto) !important;
            align-items: center !important;
            gap: 12px !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 9px 12px !important;
            border: 0 !important;
            border-bottom: 1px solid #d9e2ec !important;
            border-radius: 0 !important;
            background: #ffffff !important;
            line-height: 1.5 !important;
        }

        .quotation-print .totals .total-row:last-child,
        .invoice-print .totals .total-row:last-child,
        .sales-invoice-print .totals .total-row:last-child {
            border-bottom: 0 !important;
        }

        .quotation-print .totals .total-row > span:first-child,
        .invoice-print .totals .total-row > span:first-child,
        .sales-invoice-print .totals .total-row > span:first-child {
            color: #334155 !important;
            font-weight: 600 !important;
            text-align: right !important;
            white-space: normal !important;
        }

        .quotation-print .totals .total-row > span:last-child,
        .invoice-print .totals .total-row > span:last-child,
        .sales-invoice-print .totals .total-row > span:last-child {
            direction: ltr !important;
            unicode-bidi: isolate !important;
            color: #172033 !important;
            font-weight: 700 !important;
            text-align: left !important;
            white-space: nowrap !important;
        }

        .quotation-print .totals .grand-total,
        .invoice-print .totals .grand-total,
        .sales-invoice-print .totals .grand-total {
            background: #173a5e !important;
            color: #ffffff !important;
            font-size: 15px !important;
            font-weight: 800 !important;
        }

        .quotation-print .totals .grand-total > span,
        .invoice-print .totals .grand-total > span,
        .sales-invoice-print .totals .grand-total > span {
            color: #ffffff !important;
            font-weight: 800 !important;
        }

        @media screen and (max-width: 720px) {
            .quotation-print .totals-wrap .totals,
            .invoice-print .totals-wrap .totals,
            .sales-invoice-print .totals-wrap .totals {
                width: 100% !important;
            }
        }
    </style>
<style>
    /* FINAL-TOTAL-WHITE-FIX */

    .grand-total,
    .grand-total *,
    .totals .grand-total,
    .totals .grand-total *,
    .totals-card .grand-total,
    .totals-card .grand-total * {
        color: #ffffff !important;
    }

    .grand-total {
        background: #123b67 !important;
        font-weight: 800 !important;
    }

    .grand-total .total-label,
    .grand-total .total-value,
    .grand-total span,
    .grand-total strong {
        color: #ffffff !important;
        opacity: 1 !important;
        text-shadow: none !important;
    }
</style>

<style>
    /* OCTRAM-DOCUMENT-HEADER-V2 */

    .company-document-header {
        margin-bottom: 18px !important;
        padding: 15px 17px !important;
        border: 1px solid #bfd0e2 !important;
        border-top: 5px solid #123b67 !important;
        border-radius: 10px !important;
        background: #ffffff !important;
    }

    .company-document-layout {
        display: grid !important;
        grid-template-columns:
            minmax(185px, .8fr)
            minmax(260px, 1.4fr)
            minmax(135px, .65fr) !important;
        align-items: center !important;
        gap: 18px !important;
        direction: rtl !important;
    }

    .company-document-details {
        min-width: 0 !important;
    }

    .company-document-title {
        display: inline-block !important;
        margin-bottom: 10px !important;
        padding: 8px 18px !important;
        border-radius: 7px !important;
        background: #123b67 !important;
        color: #ffffff !important;
        font-size: 22px !important;
        font-weight: 800 !important;
    }

    .company-document-meta {
        display: grid !important;
        gap: 5px !important;
    }

    .company-document-meta > div {
        display: grid !important;
        grid-template-columns: 85px 1fr !important;
        gap: 7px !important;
    }

    .company-document-meta span {
        color: #64748b !important;
    }

    .company-document-meta strong {
        color: #172033 !important;
    }

    .company-document-company {
        min-width: 0 !important;
        text-align: center !important;
    }

    .company-document-company-name {
        color: #123b67 !important;
        font-size: 20px !important;
        font-weight: 800 !important;
    }

    .company-document-activity {
        margin-top: 3px !important;
        color: #475569 !important;
        font-size: 12px !important;
        font-weight: 700 !important;
    }

    .company-document-contact-lines {
        margin-top: 8px !important;
        color: #475569 !important;
        font-size: 10.5px !important;
        line-height: 1.65 !important;
    }

    .company-document-legal {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
        gap: 12px !important;
    }

    .company-document-logo-wrap {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center !important;
        direction: ltr !important;
    }

    .company-document-logo {
        width: auto !important;
        max-width: 145px !important;
        height: auto !important;
        max-height: 90px !important;
        object-fit: contain !important;
    }

    .company-document-logo-placeholder {
        display: grid !important;
        place-items: center !important;
        width: 130px !important;
        height: 72px !important;
        border: 1px dashed #94a3b8 !important;
        border-radius: 8px !important;
        color: #123b67 !important;
        font-size: 18px !important;
        font-weight: 800 !important;
    }

    @media screen and (max-width: 760px) {
        .company-document-layout {
            grid-template-columns: 1fr !important;
        }

        .company-document-details,
        .company-document-company,
        .company-document-logo-wrap {
            text-align: center !important;
            justify-content: center !important;
        }
    }
</style>

<style>
    /* SALES-INVOICE-GRAND-TOTAL-V3 */

    .quotation-print .totals .total-row.grand-total {
        background: #123b67 !important;
    }

    .quotation-print .totals .total-row.grand-total,
    .quotation-print .totals .total-row.grand-total *,
    .quotation-print .totals .total-row.grand-total span,
    .quotation-print .totals .total-row.grand-total strong,
    .quotation-print .totals .grand-total-label,
    .quotation-print .totals .grand-total-amount {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        opacity: 1 !important;
    }

    .quotation-print .totals .grand-total-label,
    .quotation-print .totals .grand-total-amount {
        font-size: 15px !important;
        font-weight: 800 !important;
    }

    .quotation-print .totals .grand-total-amount {
        direction: ltr !important;
        unicode-bidi: isolate !important;
        text-align: left !important;
        white-space: nowrap !important;
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
        document-title="فاتورة مبيعات"
        :document-number="$invoice->document_number"
        :document-date="$invoice->invoice_date->format('d/m/Y')"
        :expiry-date="$invoice->due_date?->format('d/m/Y')"
    />

    <section class="info-grid" aria-label="بيانات العميل وعرض السعر">
        <div class="info-row"><span class="info-label">العميل</span><strong>{{ $invoice->customer->name }}</strong></div>

        @if ($invoice->customerPurchaseOrder)
            <div class="info-row">
                <span class="info-label">رقم أمر توريد العميل</span>
                <strong class="numeric">
                    {{ $invoice->customerPurchaseOrder->document_number }}
                </strong>
            </div>
        @endif
        @if (filled($invoice->customer->mobile ?? $invoice->customer->phone))
            <div class="info-row"><span class="info-label">رقم الهاتف</span><span class="numeric">{{ $invoice->customer->mobile ?? $invoice->customer->phone }}</span></div>
        @endif
        @if (filled($invoice->customer->address))
            <div class="info-row"><span class="info-label">العنوان</span><span>{{ $invoice->customer->address }}</span></div>
        @endif
        @if (filled($invoice->customer->tax_number))
            <div class="info-row"><span class="info-label">الرقم الضريبي</span><span class="numeric">{{ $invoice->customer->tax_number }}</span></div>
        @endif
    </section>

    <table aria-label="أصناف فاتورة المبيعات">
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
            <th>الإجمالي بعد الضريبة</th>
        </tr>
        </thead>
        <tbody>
@foreach ($invoice->items as $item)
    <tr class="item-row">
        <td class="numeric">
            {{ $loop->iteration }}
        </td>

        <td class="item-code">
            {{ $item->item->code }}
        </td>

        <td class="description">
            {{ $item->item->name }}
        </td>

        <td>
            {{ $item->unit?->name ?: '—' }}
        </td>

        <td class="numeric">
            {{ \App\Support\QuantityFormatter::formatForDisplay($item->quantity) }}
        </td>

        <td class="numeric">
            {{ number_format((float) $item->unit_price, 2) }} ج.م
        </td>

        <td class="numeric">
            {{ number_format((float) $item->discount_amount, 2) }} ج.م
        </td>

        <td class="numeric">
            {{ number_format((float) $item->tax_amount, 2) }} ج.م
        </td>

        <td class="numeric">
            <strong>
                {{ number_format(
                    (float) $item->line_total + (float) $item->tax_amount,
                    2
                ) }} ج.م
            </strong>
        </td>
    </tr>
@endforeach
</tbody>

    </table>
@php
    $subtotalBeforeTax = (float) $invoice->items->sum('line_total');

    $itemsDiscount = (float) $invoice->items->sum('discount_amount');
    $invoiceDiscount = (float) ($invoice->discount_amount ?? 0);
    $totalDiscount = $itemsDiscount + $invoiceDiscount;

    $calculatedTax = (float) $invoice->items->sum('tax_amount');
    $taxAmount = $calculatedTax > 0
        ? $calculatedTax
        : (float) ($invoice->tax_amount ?? 0);

    $grandTotal = $invoice->totalAmount();
@endphp
   <div class="totals-wrap">
    <section class="totals" aria-label="إجماليات فاتورة المبيعات">

        <div class="total-row">
            <span>إجمالي الأصناف قبل الضريبة</span>

            <span class="numeric">
                {{ number_format($subtotalBeforeTax, 2) }} ج.م
            </span>
        </div>

        <div class="total-row">
            <span>إجمالي الخصم</span>

            <span class="numeric">
                {{ number_format($totalDiscount, 2) }} ج.م
            </span>
        </div>

        <div class="total-row">
            <span>
                {{ $invoice->tax_type === \App\Enums\TaxType::Vat14
                    ? 'إجمالي ضريبة القيمة المضافة (14%)'
                    : 'إجمالي الضريبة' }}
            </span>

            <span class="numeric">
                {{ number_format($taxAmount, 2) }} ج.م
            </span>
        </div>

        @if ((float) ($invoice->one_percent_discount_amount ?? 0) > 0)
            <div class="total-row">
                <span>خصم وإضافة (1%)</span>

                <span class="numeric">
                    {{ number_format((float) $invoice->one_percent_discount_amount, 2) }} ج.م
                </span>
            </div>
        @endif
        @if ((float) ($invoice->service_tax_discount_amount ?? 0) > 0)
            <div class="total-row">
                <span>خصم ضريبة خدمات (3%)</span>
                <span class="numeric">
                    {{ number_format((float) $invoice->service_tax_discount_amount, 2) }} ج.م
                </span>
            </div>
        @endif

        <div class="total-row grand-total">
            <span class="grand-total-label">صافي الفاتورة</span>

            <span class="grand-total-amount">
                {{ number_format($grandTotal, 2) }} ج.م
            </span>
        </div>

    </section>
</div>
            <div class="total-row grand-total"><span class="grand-total-label">الإجمالي الكلي</span><span class="grand-total-amount">{{ number_format($invoice->totalAmount(), 2) }} ج.م</span></div>
        </section>
    </div>

    @if (filled($invoice->terms_and_conditions))
        <section class="text-block">
            <div class="block-title">شروط العرض</div>
            <div>{{ $invoice->terms_and_conditions }}</div>
        </section>
    @endif

    @if (filled($invoice->notes))
        <section class="text-block">
            <div class="block-title">ملاحظات</div>
            <div>{{ $invoice->notes }}</div>
        </section>
    @endif

    <section class="signatures" aria-label="التوقيعات">
        <div class="signature">توقيع العميل</div>
        <div class="signature">اعتماد الشركة</div>
    </section>

    <footer class="footer">
        فاتورة مبيعات رقم <span class="numeric">{{ $invoice->document_number }}</span>
    </footer>
</main>
</body>
</html>
