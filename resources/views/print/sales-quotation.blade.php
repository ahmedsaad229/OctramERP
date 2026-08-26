<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>عرض سعر {{ $quotation->quotation_number }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        :root {
            --primary: #123b67;
            --primary-dark: #0d2f54;
            --primary-light: #edf5fc;
            --border: #b9cce0;
            --muted: #64748b;
            --text: #172033;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #edf2f7;
            color: var(--text);
            font-family: Arial, Tahoma, sans-serif;
            line-height: 1.5;
        }

        .quotation-page {
            width: min(210mm, calc(100% - 30px));
            min-height: 281mm;
            margin: 20px auto;
            padding: 9mm;
            background: var(--white);
            box-shadow: 0 8px 28px rgb(15 23 42 / 12%);
            font-size: 11.5px;
        }

        .toolbar {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 12px;
        }

        .print-button {
            border: 0;
            border-radius: 7px;
            padding: 9px 18px;
            background: #2563eb;
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            box-shadow: 0 4px 12px rgb(37 99 235 / 22%);
        }

        /* ترويسة الشركة الموجودة بالفعل في النظام */
        /* ===== OCTRAM HEADER ===== */

.document-header{
    margin-bottom:18px;
    padding:15px 18px;
    border:1px solid #bfd0e2;
    border-top:5px solid #123b67;
    border-radius:10px;
    background:#fff;
}

.company-document-layout{
    display:grid;
    grid-template-columns:170px 1fr 240px;
    align-items:center;
    gap:20px;
}

.company-document-logo{
    max-width:145px;
    max-height:90px;
    object-fit:contain;
}

.company-document-company{
    text-align:center;
}

.company-document-company-name{
    color:#123b67;
    font-size:20px;
    font-weight:800;
}

.company-document-contact-lines{
    margin-top:8px;
    color:#475569;
    font-size:10px;
    line-height:1.7;
}

.company-document-title{
    display:inline-block;
    padding:8px 18px;
    background:#123b67;
    color:#fff;
    border-radius:8px;
    font-size:22px;
    font-weight:800;
}

/* ===== OCTRAM HEADER V2 ===== */

.document-header {
    margin-bottom: 18px !important;
    padding: 14px 16px !important;
    border: 1px solid #bfd0e2 !important;
    border-top: 5px solid #123b67 !important;
    border-radius: 10px !important;
    background: #ffffff !important;
}

/*
 ترتيب الهيدر:
 يمين: عنوان المستند وبياناته
 وسط: اسم الشركة والاتصال
 شمال: اللوجو
*/
.company-document-layout {
    display: grid !important;
    grid-template-columns:
        minmax(195px, 230px)
        minmax(300px, 1fr)
        minmax(125px, 150px) !important;
    align-items: center !important;
    gap: 14px !important;
    width: 100% !important;
    direction: rtl !important;
}

/* بيانات المستند في اليمين */
.company-document-details {
    min-width: 0 !important;
    text-align: right !important;
    justify-self: stretch !important;
}

/* كلمة عرض سعر فوق الرقم والتاريخ */
.company-document-title {
    display: block !important;
    width: 100% !important;
    margin: 0 0 8px !important;
    padding: 7px 14px !important;
    border-radius: 8px !important;
    background: #123b67 !important;
    color: #ffffff !important;
    font-size: 22px !important;
    font-weight: 800 !important;
    line-height: 1.25 !important;
    text-align: center !important;
    white-space: nowrap !important;
}

/* رقم عرض السعر والتاريخ وصالح حتى */
.company-document-meta {
    display: grid !important;
    gap: 4px !important;
    width: 100% !important;
}

.company-document-meta > div {
    display: grid !important;
    grid-template-columns: 72px minmax(0, 1fr) !important;
    align-items: center !important;
    gap: 7px !important;
}

.company-document-meta span {
    color: #64748b !important;
    font-size: 10px !important;
    white-space: nowrap !important;
}

.company-document-meta strong {
    color: #172033 !important;
    font-size: 10.5px !important;
    font-weight: 800 !important;
    text-align: left !important;
    white-space: nowrap !important;
}

/* اسم الشركة وبيانات الاتصال في المنتصف */
.company-document-company {
    min-width: 0 !important;
    text-align: center !important;
    justify-self: stretch !important;
}

.company-document-company-name {
    display: block !important;
    width: 100% !important;
    color: #123b67 !important;
    font-size: 19px !important;
    font-weight: 800 !important;
    line-height: 1.3 !important;
    text-align: center !important;
    white-space: nowrap !important;
}

.company-document-activity {
    margin-top: 2px !important;
    color: #475569 !important;
    font-size: 10.5px !important;
    font-weight: 700 !important;
    white-space: nowrap !important;
}

.company-document-contact-lines {
    margin-top: 6px !important;
    color: #475569 !important;
    font-size: 9px !important;
    line-height: 1.55 !important;
    text-align: center !important;
}

.company-document-legal {
    display: flex !important;
    flex-wrap: wrap !important;
    justify-content: center !important;
    gap: 8px !important;
}

/* اللوجو في أقصى الشمال */
.company-document-logo-wrap {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    justify-self: start !important;
    width: 100% !important;
    direction: ltr !important;
    margin: 0 !important;
    padding: 0 !important;
}

.company-document-logo {
    display: block !important;
    width: auto !important;
    max-width: 135px !important;
    height: auto !important;
    max-height: 82px !important;
    margin: 0 !important;
    object-fit: contain !important;
}

.company-document-logo-placeholder {
    width: 125px !important;
    height: 68px !important;
    margin: 0 !important;
}

/* الهاتف المحمول */
@media screen and (max-width: 760px) {
    .company-document-layout {
        grid-template-columns: 1fr !important;
    }

    .company-document-details,
    .company-document-company {
        text-align: center !important;
    }

    .company-document-logo-wrap {
        justify-content: center !important;
        justify-self: center !important;
    }

    .company-document-company-name {
        white-space: normal !important;
    }

}

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: 17px 0;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .meta-item {
            min-height: 72px;
            padding: 11px 13px;
            border-left: 1px solid var(--border);
            text-align: center;
        }

        .meta-item:last-child {
            border-left: 0;
        }

        .meta-label {
            margin-bottom: 6px;
            color: var(--muted);
            font-size: 11px;
        }

        .meta-value {
            color: var(--primary-dark);
            font-size: 14px;
            font-weight: 800;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: isolate;
        }

        .customer-panel {
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            margin-bottom: 16px;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .customer-block {
            min-height: 78px;
            padding: 12px 15px;
            border-left: 1px solid var(--border);
        }

        .customer-block:last-child {
            border-left: 0;
        }

        .section-label {
            margin-bottom: 5px;
            color: var(--muted);
            font-size: 11px;
        }

        .section-value {
            font-size: 13px;
            font-weight: 700;
        }

        .customer-extra {
            margin-top: 5px;
            color: #475569;
            font-size: 10.5px;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table th {
            padding: 8px 5px;
            border-left: 1px solid #7796b5;
            background: var(--primary);
            color: #fff;
            font-weight: 800;
            text-align: center;
            white-space: nowrap;
        }

        .items-table th:last-child {
            border-left: 0;
        }

        .items-table td {
            min-height: 38px;
            padding: 8px 5px;
            border-left: 1px solid #d7e1eb;
            border-bottom: 1px solid #d7e1eb;
            vertical-align: middle;
        }

        .items-table td:last-child {
            border-left: 0;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8fbfe;
        }

        .numeric,
        .item-code {
            direction: ltr;
            unicode-bidi: isolate;
            text-align: center;
            white-space: nowrap;
        }

        .description {
            overflow-wrap: anywhere;
            text-align: right;
        }

        .summary-section {
            display: flex;
            justify-content: flex-start;
            margin-top: 16px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .totals-card {
            width: min(100%, 430px);
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
        }

        .total-row {
            display: grid;
            grid-template-columns: minmax(190px, 1fr) minmax(125px, auto);
            align-items: center;
            gap: 15px;
            min-height: 42px;
            padding: 9px 13px;
            border-bottom: 1px solid #d9e3ed;
        }

        .total-row:last-child {
            border-bottom: 0;
        }

        .total-label {
            font-weight: 600;
        }

        .total-value {
            direction: ltr;
            unicode-bidi: isolate;
            font-weight: 800;
            text-align: left;
            white-space: nowrap;
        }

        .grand-total {
            background: var(--primary);
            color: #fff;
            font-size: 15px;
            font-weight: 800;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 15px;
        }

        .text-card {
            min-height: 92px;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
            white-space: pre-line;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .text-card-title {
            margin-bottom: 7px;
            color: var(--primary);
            font-weight: 800;
        }

        .empty-text {
            color: var(--muted);
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 85px;
            margin-top: 44px;
            padding: 0 24px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .signature {
            min-height: 65px;
            padding-top: 11px;
            border-top: 1px solid #4b6075;
            color: var(--primary-dark);
            font-weight: 700;
            text-align: center;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 10px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        @media screen and (max-width: 760px) {
            .quotation-page {
                width: 100%;
                margin: 0;
                padding: 15px;
                overflow-x: auto;
                box-shadow: none;
            }

            .meta-grid,
            .customer-panel,
            .details-grid,
            .signatures {
                grid-template-columns: 1fr;
            }

            .meta-item,
            .customer-block {
                border-left: 0;
                border-bottom: 1px solid var(--border);
            }

            .meta-item:last-child,
            .customer-block:last-child {
                border-bottom: 0;
            }

            .totals-card {
                width: 100%;
            }
        }

        @media print {
            body {
                background: #fff;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .quotation-page {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .toolbar {
                display: none !important;
            }

            .items-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .items-table tbody tr:nth-child(even) {
                background: #f8fbfe !important;
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
    /* SALES-RESPONSIBLE-PRINT-CARD */

    .sales-responsible-card {
        display: grid;
        grid-template-columns: 150px minmax(0, 1fr) minmax(190px, auto);
        align-items: center;
        gap: 14px;
        margin: 0 0 16px;
        padding: 11px 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: linear-gradient(180deg, #ffffff, #f8fbfe);
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .sales-responsible-title {
        color: var(--muted);
        font-size: 11px;
        font-weight: 700;
    }

    .sales-responsible-name {
        color: var(--primary-dark);
        font-size: 13px;
        font-weight: 800;
    }

    .sales-responsible-contact {
        direction: ltr;
        unicode-bidi: isolate;
        color: #475569;
        font-size: 10.5px;
        text-align: left;
        white-space: nowrap;
    }

    @media screen and (max-width: 760px) {
        .sales-responsible-card {
            grid-template-columns: 1fr;
        }

        .sales-responsible-contact {
            text-align: right;
        }
    }
</style>
<style>
    /* SALES-RESPONSIBLE-DETAILS-V2 */

    .sales-responsible-job-title {
        margin-top: 3px;
        color: #64748b;
        font-size: 10.5px;
        font-weight: 700;
    }

    .sales-responsible-contact {
        direction: ltr;
        unicode-bidi: isolate;
        text-align: left;
        line-height: 1.6;
    }
</style>

<style>
    /* FINAL-QUOTATION-NUMERIC-COLUMNS-FIX */

    .items-table {
        table-layout: fixed !important;
        width: 100% !important;
    }

    .items-table th,
    .items-table td {
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    .items-table td.numeric,
    .items-table td.item-code {
        direction: ltr !important;
        unicode-bidi: isolate !important;
        white-space: nowrap !important;
        text-align: center !important;
        font-variant-numeric: tabular-nums;
    }

    .items-table td.numeric {
        font-size: 9px !important;
        letter-spacing: -0.15px !important;
        padding-left: 3px !important;
        padding-right: 3px !important;
    }

    .items-table td.numeric strong {
        display: inline-block !important;
        white-space: nowrap !important;
        font-size: inherit !important;
    }

    .items-table th {
        font-size: 9.5px !important;
        padding-left: 3px !important;
        padding-right: 3px !important;
    }

    .items-table td.description {
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        line-height: 1.55 !important;
    }

    @media print {
        .items-table td.numeric {
            font-size: 8.8px !important;
        }

        .items-table th {
            font-size: 9px !important;
        }
    }
</style>
</head>

<body>
@php
    $subtotal = (float) ($quotation->subtotal ?? $quotation->items->sum('line_total'));
    $discount = (float) ($quotation->discount_amount ?? 0);
    $taxAmount = (float) ($quotation->tax_amount ?? 0);
    $totalAmount = (float) (
        $quotation->total_amount
        ?? max(0, $subtotal - $discount + $taxAmount)
    );
    $showDiscountColumn = $quotation->items->contains(fn ($item): bool => (float) $item->discount_amount > 0.009);
@endphp

<main class="quotation-page">
    <div class="toolbar">
        <button type="button" class="print-button" onclick="window.print()">
            طباعة / حفظ PDF
        </button>
    </div>

    <x-company-document-header
        class="document-header"
        :settings="$settings"
        document-title="عرض سعر"
        :document-number="$quotation->quotation_number"
        :document-date="$quotation->quotation_date?->format('d/m/Y')"
        :expiry-date="$quotation->valid_until?->format('d/m/Y')"
    />

    <section class="meta-grid" aria-label="بيانات عرض السعر">
        <div class="meta-item">
            <div class="meta-label">رقم عرض السعر</div>
            <div class="meta-value ltr">
                {{ $quotation->quotation_number }}
            </div>
        </div>

        <div class="meta-item">
            <div class="meta-label">التاريخ</div>
            <div class="meta-value ltr">
                {{ $quotation->quotation_date?->format('d/m/Y') ?: '—' }}
            </div>
        </div>

        <div class="meta-item">
            <div class="meta-label">صالح حتى</div>
            <div class="meta-value ltr">
                {{ $quotation->valid_until?->format('d/m/Y') ?: '—' }}
            </div>
        </div>
    </section>

    <section class="customer-panel" aria-label="بيانات العميل">
        <div class="customer-block">
            <div class="section-label">العميل</div>
            <div class="section-value">
                {{ $quotation->customer->name }}
            </div>

            @if (filled($quotation->customer->mobile ?? $quotation->customer->phone))
                <div class="customer-extra">
                    الهاتف:
                    <span class="ltr">
                        {{ $quotation->customer->mobile ?? $quotation->customer->phone }}
                    </span>
                </div>
            @endif

            @if (filled($quotation->customer->tax_number))
                <div class="customer-extra">
                    الرقم الضريبي:
                    <span class="ltr">{{ $quotation->customer->tax_number }}</span>
                </div>
            @endif
        </div>

        <div class="customer-block">
            <div class="section-label">العنوان</div>
            <div class="section-value">
                {{ $quotation->customer->address ?: '—' }}
            </div>
        </div>
    </section>


    @php
        $salesResponsible = $quotation->salesResponsible ?? $quotation->creator;
        $showItemDiscount = $quotation->items->contains(
        fn ($item): bool => abs((float) ($item->discount_amount ?? 0)) > 0.005
    );
    $itemsTableColumns = $showItemDiscount ? 10 : 9;
@endphp

    @if ($salesResponsible)
        <section class="sales-responsible-card" aria-label="مسؤول المبيعات">
            <div class="sales-responsible-title">
                مسؤول المبيعات
            </div>

            <div>
                <div class="sales-responsible-name">
                    {{ $salesResponsible->name }}
                </div>

                @if (filled($salesResponsible->job_title))
                    <div class="sales-responsible-job-title">
                        {{ $salesResponsible->job_title }}
                    </div>
                @endif
            </div>

            <div class="sales-responsible-contact">
                @if (filled($salesResponsible->mobile))
                    <div>{{ $salesResponsible->mobile }}</div>
                @endif

                @if (filled($salesResponsible->email))
                    <div>{{ $salesResponsible->email }}</div>
                @endif
            </div>
        </section>
    @endif

    <table class="items-table" aria-label="أصناف عرض السعر">
                @if ($showDiscountColumn)
            <colgroup>
                <col style="width: 3%">
                <col style="width: 10%">
                <col style="width: 22%">
                <col style="width: 5%">
                <col style="width: 5%">
                <col style="width: 11%">
                <col style="width: 11%">
                <col style="width: 10%">
                <col style="width: 11%">
                <col style="width: 12%">
            </colgroup>
        @else
            <colgroup>
                <col style="width: 3%">
                <col style="width: 11%">
                <col style="width: 25%">
                <col style="width: 6%">
                <col style="width: 6%">
                <col style="width: 13%">
                <col style="width: 13%">
                <col style="width: 11%">
                <col style="width: 12%">
            </colgroup>
        @endif

        <thead>
        <tr>
            <th>م</th>
            <th>كود الصنف</th>
            <th>بيان الصنف</th>
            <th>الوحدة</th>
            <th>الكمية</th>
            <th>سعر الصنف</th>
            <th>إجمالي الصنف</th>
            @if ($showItemDiscount)
                <th>الخصم</th>
            @endif
            <th>الضريبة</th>
            <th>الإجمالي</th>
        </tr>
        </thead>

        <tbody>
        @forelse ($quotation->items as $item)
            @php
                $itemBaseTotal = round(
                    (float) $item->quantity * (float) $item->unit_price,
                    2
                );
            @endphp

            <tr>
                <td class="numeric">{{ $loop->iteration }}</td>

                <td class="item-code">
                    {{ $item->item->code }}
                </td>

                <td class="description">
                    {{ $item->item->name }}
                </td>

                <td class="numeric">
                    {{ $item->unit?->name ?: '—' }}
                </td>

                <td class="numeric">
                    {{ \App\Support\QuantityFormatter::formatForDisplay($item->quantity) }}
                </td>

                <td class="numeric">
                    {{ number_format((float) $item->unit_price, 2) }} ج.م
                </td>

                <td class="numeric">
                    {{ number_format($itemBaseTotal, 2) }} ج.م
                </td>

                @if ($showItemDiscount)
                    <td class="numeric">
                        {{ number_format((float) $item->discount_amount, 2) }} ج.م
                    </td>
                @endif

                <td class="numeric">
                    {{ number_format((float) $item->tax_amount, 2) }} ج.م
                </td>

                <td class="numeric">
                    <strong>
                        {{ number_format((float) $item->line_total, 2) }} ج.م
                    </strong>
                </td>
            </tr>

            @if (filled($item->notes))
                <tr class="item-notes-row">
                    <td colspan="{{ $itemsTableColumns }}"
                        style="background:#f8fbff;padding:8px 12px;text-align:right;font-size:11px;color:#334155;">
                        <strong style="color:#123b67">
                            ملاحظات البند :
                        </strong>
                        {{ $item->notes }}
                    </td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="{{ $itemsTableColumns }}" style="padding: 25px; text-align: center; color: #64748b;">
                    لا توجد أصناف في عرض السعر.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <section class="summary-section">
        <div class="totals-card">
            <div class="total-row">
                <span class="total-label">إجمالي الأصناف قبل الخصم والضريبة</span>
                <span class="total-value">
                    {{ number_format($quotation->exclude_totals ? 0 : $subtotal, 2) }} ج.م
                </span>
            </div>

            @if (abs((float) $discount) > 0.005)
                <div class="total-row">
                    <span class="total-label">الخصم</span>
                    <span class="total-value">
                        {{ number_format($quotation->exclude_totals ? 0 : $discount, 2) }} ج.م
                    </span>
                </div>
            @endif
<div class="total-row">
                <span class="total-label">الضريبة</span>
                <span class="total-value">
                    {{ number_format($quotation->exclude_totals ? 0 : $taxAmount, 2) }} ج.م
                </span>
            </div>

            <div class="total-row grand-total">
                <span>الإجمالي الكلي</span>
                <span class="total-value">
                    {{ number_format($quotation->exclude_totals ? 0 : $totalAmount, 2) }} ج.م
                </span>
            </div>
        </div>
    </section>

    @if (filled($quotation->notes) || filled($quotation->terms_and_conditions))
        <section class="details-grid">
            <div class="text-card">
                <div class="text-card-title">ملاحظات</div>

                @if (filled($quotation->notes))
                    {{ $quotation->notes }}
                @else
                    <span class="empty-text">لا توجد ملاحظات.</span>
                @endif
            </div>

            <div class="text-card">
                <div class="text-card-title">الشروط والأحكام</div>

                @if (filled($quotation->terms_and_conditions))
                    {{ $quotation->terms_and_conditions }}
                @else
                    <span class="empty-text">لا توجد شروط وأحكام إضافية.</span>
                @endif
            </div>
        </section>
    @endif

    <section class="signatures">
        <div class="signature">توقيع العميل</div>
        <div class="signature">اعتماد الشركة</div>
    </section>

    <footer class="footer">
        <span class="ltr">{{ $quotation->quotation_number }}</span>
        <span>عرض سعر صادر من {{ $settings->commercialName() }}</span>
    </footer>
</main>
</body>
</html>

<style>
    /* QUOTATION-NUMBERS-BOLD-FIX */

    .items-table td.numeric,
    .items-table td.item-code {
        font-weight: 800 !important;
        color: #0f2f50 !important;
    }

    .items-table td.numeric strong {
        font-weight: 900 !important;
        color: #0b2a49 !important;
    }

    .items-table td.item-code {
        font-weight: 800 !important;
    }

    .totals-card .total-value {
        font-weight: 900 !important;
        color: #102f4d !important;
    }

    .totals-card .grand-total .total-value {
        color: #ffffff !important;
        font-weight: 900 !important;
    }
</style>

