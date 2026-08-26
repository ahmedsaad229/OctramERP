<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>سند استلام نقدية {{ $voucher->document_number }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        :root {
            --primary: #123b67;
            --primary-dark: #0d2f54;
            --primary-soft: #edf5fc;
            --border: #bfd0e2;
            --text: #172033;
            --muted: #64748b;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef0f3;
            color: var(--text);
            font-family: Arial, Tahoma, sans-serif;
            line-height: 1.5;
        }

        .voucher {
            width: min(210mm, calc(100% - 30px));
            min-height: 281mm;
            margin: 20px auto;
            padding: 9mm;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 8px 28px rgb(15 23 42 / 12%);
            font-size: 11px;
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

        .company-document-header,
        .document-header {
            margin-bottom: 18px !important;
            padding: 15px 17px !important;
            border: 1px solid var(--border) !important;
            border-top: 5px solid var(--primary) !important;
            border-radius: 10px !important;
            background: #fff !important;
        }

        .company-document-layout {
            display: grid !important;
            grid-template-columns:
                minmax(190px, .8fr)
                minmax(300px, 1.5fr)
                minmax(135px, .65fr) !important;
            align-items: center !important;
            gap: 18px !important;
            direction: rtl !important;
        }

        .company-document-details {
            min-width: 0 !important;
            text-align: right !important;
        }

        .company-document-title {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            min-height: 48px !important;
            margin: 0 0 9px !important;
            padding: 8px 10px !important;
            border-radius: 8px !important;
            background: var(--primary) !important;
            color: #fff !important;
            font-size: 20px !important;
            font-weight: 800 !important;
            text-align: center !important;
            white-space: nowrap !important;
        }

        .company-document-meta {
            display: grid !important;
            gap: 4px !important;
            width: 100% !important;
        }

        .company-document-meta > div {
            display: grid !important;
            grid-template-columns: 80px minmax(0, 1fr) !important;
            gap: 7px !important;
            align-items: center !important;
        }

        .company-document-meta span {
            color: var(--muted) !important;
            font-size: 10px !important;
        }

        .company-document-meta strong {
            color: var(--text) !important;
            font-size: 10.5px !important;
        }

        .company-document-company {
            min-width: 0 !important;
            text-align: center !important;
        }

        .company-document-company-name {
            color: var(--primary) !important;
            font-size: 19px !important;
            font-weight: 800 !important;
            white-space: nowrap !important;
        }

        .company-document-activity {
            margin-top: 2px !important;
            color: #475569 !important;
            font-size: 10.5px !important;
            font-weight: 700 !important;
        }

        .company-document-contact-lines {
            margin-top: 6px !important;
            color: #475569 !important;
            font-size: 9px !important;
            line-height: 1.55 !important;
        }

        .company-document-legal {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            gap: 8px !important;
        }

        .company-document-logo-wrap {
            display: flex !important;
            justify-content: flex-start !important;
            align-items: center !important;
            justify-self: start !important;
            width: 100% !important;
            direction: ltr !important;
        }

        .company-document-logo {
            width: auto !important;
            max-width: 135px !important;
            height: auto !important;
            max-height: 82px !important;
            object-fit: contain !important;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin: 14px 0;
        }

        .summary-card {
            min-height: 72px;
            padding: 11px 13px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: linear-gradient(180deg, #fff, #f8fbfe);
            box-shadow: 0 2px 7px rgb(15 23 42 / 5%);
        }

        .summary-label {
            color: var(--muted);
            font-size: 10.5px;
        }

        .summary-value {
            margin-top: 5px;
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 800;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .amount-card {
            display: grid;
            grid-template-columns: minmax(190px, 1fr) minmax(220px, auto);
            align-items: center;
            gap: 15px;
            margin: 16px 0;
            padding: 14px 16px;
            border: 1px solid #86b6e6;
            border-radius: 9px;
            background: var(--primary-soft);
        }

        .amount-label {
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 800;
        }

        .amount-value {
            padding: 10px 18px;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            font-size: 20px;
            font-weight: 900;
            text-align: center;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .detail-card {
            min-height: 78px;
            padding: 11px 13px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
        }

        .detail-card.full {
            grid-column: 1 / -1;
        }

        .detail-label {
            margin-bottom: 6px;
            color: var(--muted);
            font-size: 10.5px;
        }

        .detail-value {
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 800;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 45px;
            margin-top: 48px;
            padding: 0 20px;
            text-align: center;
        }

        .signature {
            min-height: 68px;
            padding-top: 11px;
            border-top: 1px solid #4b6075;
            color: var(--primary-dark);
            font-weight: 700;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 10px;
        }

        @media screen and (max-width: 760px) {
            .voucher {
                width: 100%;
                margin: 0;
                padding: 15px;
                box-shadow: none;
            }

            .company-document-layout,
            .summary-grid,
            .details,
            .signatures,
            .amount-card {
                grid-template-columns: 1fr !important;
            }

            .company-document-logo-wrap {
                justify-content: center !important;
                justify-self: center !important;
            }

            .company-document-company-name {
                white-space: normal !important;
            }
        }

        @media print {
            body {
                background: #fff;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .voucher {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .toolbar {
                display: none !important;
            }
        }
    </style>
</head>

<body>
<main class="voucher">
    <div class="toolbar">
        <button
            type="button"
            class="print-button"
            onclick="window.print()"
        >
            طباعة / حفظ PDF
        </button>
    </div>

    <x-company-document-header
        class="document-header"
        :settings="$settings"
        document-title="سند استلام نقدية"
        :document-number="$voucher->document_number"
        :document-date="$voucher->date->format('d/m/Y')"
    />

    <section class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">نوع الاستلام</div>
            <div class="summary-value">
                {{ $voucher->receipt_type_label }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">الخزينة</div>
            <div class="summary-value">
                {{ $voucher->treasury->name }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">طريقة الاستلام</div>
            <div class="summary-value">
                {{ $voucher->payment_method->label() }}
            </div>
        </div>
    </section>

    <section class="amount-card">
        <div>
            <div class="amount-label">استلمنا من السيد / الجهة</div>
            <div style="margin-top:6px; font-weight:800;">
                {{ $voucher->receivedFromName() }}
            </div>
        </div>

        <div class="amount-value ltr">
            {{ \App\Support\ArabicMoney::format($voucher->amount) }}
        </div>
    </section>

    <section class="details">
        <div class="detail-card">
            <div class="detail-label">الرقم المرجعي</div>
            <div class="detail-value ltr">
                {{ $voucher->reference_number ?: '—' }}
            </div>
        </div>

        <div class="detail-card">
            <div class="detail-label">قيمة السند</div>
            <div class="detail-value ltr">
                {{ number_format((float) $voucher->amount, 2) }} ج.م
            </div>
        </div>

        <div class="detail-card full">
            <div class="detail-label">وذلك عن</div>
            <div class="detail-value">
                {{ $voucher->notes ?: ($voucher->receipt_reason_label ?: '—') }}
            </div>
        </div>
    </section>

    <section class="signatures">
        <div class="signature">المستلم</div>
        <div class="signature">الخزينة</div>
        <div class="signature">اعتماد</div>
    </section>

    <footer class="footer">
        <span dir="ltr">{{ $voucher->document_number }}</span>
        <span>
            سند استلام نقدية صادر من {{ $settings->commercialName() }}
        </span>
    </footer>
</main>
</body>
</html>
