<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">

    <title>تقرير أوكترام</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef3f8;
            color: #172033;
            font-family: Tahoma, Arial, sans-serif;
            direction: rtl;
        }

        .page {
            width: 100%;
            max-width: 1150px;
            margin: 18px auto;
            padding: 24px;
            background: #ffffff;
            border: 1px solid #d8e2ed;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(15, 23, 42, .07);
        }

        .no-print {
            text-align: center;
            margin-bottom: 18px;
        }

        .print-btn {
            padding: 9px 25px;
            border: 0;
            border-radius: 7px;
            background: #174b7d;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
        }

        .document-header {
            border: 1px solid #b9cee3;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 18px;
        }

        .document-title {
            background: #123f70;
            color: #fff;
            text-align: center;
            padding: 14px 20px;
            font-size: 24px;
            font-weight: 800;
        }

        .document-subtitle {
            text-align: center;
            padding: 10px;
            color: #64748b;
            font-size: 13px;
            background: #f8fbfe;
        }

        .two-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            align-items: start;
        }

        .report-section {
            border: 1px solid #bdd0e3;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .section-title {
            padding: 12px 15px;
            background: #174b7d;
            color: #fff;
            font-size: 18px;
            font-weight: 800;
            text-align: center;
        }

        .fields {
            display: grid;
            grid-template-columns: 1fr;
        }

        .field-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            min-height: 45px;
            border-bottom: 1px solid #dbe4ed;
        }

        .field-row:last-child {
            border-bottom: 0;
        }

        .field-label {
            padding: 11px 12px;
            background: #f3f7fb;
            color: #174b7d;
            font-weight: 800;
            border-left: 1px solid #dbe4ed;
        }

        .field-value {
            padding: 11px 12px;
            color: #172033;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .numeric {
            direction: ltr;
            unicode-bidi: isolate;
            text-align: right;
            white-space: nowrap;
            font-weight: 800;
        }

        .important {
            background: #eef6ff;
            font-size: 15px;
            font-weight: 900;
            color: #123f70;
        }

        .notes {
            margin-top: 18px;
            border: 1px solid #c9d7e5;
            border-radius: 9px;
            overflow: hidden;
        }

        .notes-title {
            background: #f3f7fb;
            color: #174b7d;
            padding: 9px 12px;
            font-weight: 800;
            border-bottom: 1px solid #c9d7e5;
        }

        .notes-body {
            min-height: 55px;
            padding: 12px;
        }

        .footer {
            margin-top: 22px;
            padding-top: 12px;
            border-top: 1px solid #dbe4ed;
            text-align: center;
            color: #7b8da2;
            font-size: 11px;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            body {
                background: #fff;
            }

            .page {
                max-width: none;
                margin: 0;
                padding: 0;
                border: 0;
                box-shadow: none;
            }

            .no-print {
                display: none !important;
            }

            .two-sections {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body>

<div class="page">

    <div class="no-print">
        <button class="print-btn" onclick="window.print()">
            طباعة التقرير
        </button>
    </div>

    <div class="document-header">

        <div class="document-title">
            تقرير أوكترام
        </div>

        <div class="document-subtitle">
            أوكترام للمقاولات والتوريدات
        </div>

    </div>

    <div class="two-sections">

        {{-- المشتريات --}}
        <section class="report-section">

            <div class="section-title">
                المشتريات
            </div>

            <div class="fields">

                <div class="field-row">
                    <div class="field-label">التاريخ</div>

                    <div class="field-value">
                        {{ $entry->purchase_date?->format('d/m/Y') ?: '—' }}
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">الصنف</div>

                    <div class="field-value">
                        {{ $entry->purchaseItem?->name ?: '—' }}
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">المورد</div>

                    <div class="field-value">
                        {{ $entry->supplier?->name ?: '—' }}
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">العنوان</div>

                    <div class="field-value">
                        {{ $entry->supplier_address ?: '—' }}
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">رقم التليفون</div>

                    <div class="field-value numeric">
                        {{ $entry->supplier_phone ?: '—' }}
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">السعر</div>

                    <div class="field-value numeric">
                        {{ number_format((float) ($entry->purchase_price ?? 0), 2) }} ج.م
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">الضريبة</div>

                    <div class="field-value numeric">
                        {{ number_format((float) ($entry->purchase_tax ?? 0), 2) }} ج.م
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">السعر شامل</div>

                    <div class="field-value numeric important">
                        {{ number_format((float) ($entry->purchase_price_including_tax ?? 0), 2) }} ج.م
                    </div>
                </div>

            </div>

        </section>


        {{-- المبيعات --}}
        <section class="report-section">

            <div class="section-title">
                المبيعات
            </div>

            <div class="fields">

                <div class="field-row">
                    <div class="field-label">التاريخ</div>

                    <div class="field-value">
                        {{ $entry->sales_date?->format('d/m/Y') ?: '—' }}
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">الصنف</div>

                    <div class="field-value">
                        {{ $entry->salesItem?->name ?: '—' }}
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">رقم الفاتورة</div>

                    <div class="field-value numeric">
                        {{ $entry->sales_invoice_number ?: '—' }}
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">العميل</div>

                    <div class="field-value">
                        {{ $entry->customer?->name ?: '—' }}
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">إجمالي الفاتورة</div>

                    <div class="field-value numeric important">
                        {{ number_format((float) ($entry->sales_invoice_total ?? 0), 2) }} ج.م
                    </div>
                </div>

            </div>

        </section>

    </div>

    @if (filled($entry->notes))
        <div class="notes">

            <div class="notes-title">
                ملاحظات
            </div>

            <div class="notes-body">
                {{ $entry->notes }}
            </div>

        </div>
    @endif

    <div class="footer">
        تقرير أوكترام
        —
        تاريخ الطباعة:
        {{ $printedAt?->format('d/m/Y H:i') }}
    </div>

</div>

</body>
</html>