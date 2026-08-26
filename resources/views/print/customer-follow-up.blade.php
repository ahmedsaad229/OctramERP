<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>متابعة عميل {{ $followUp->follow_up_number }}</title>

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
            line-height: 1.55;
        }

        .follow-up-page {
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
            width: 100% !important;
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
            grid-template-columns: 82px minmax(0, 1fr) !important;
            gap: 7px !important;
        }

        .company-document-meta span {
            color: var(--muted) !important;
        }

        .company-document-company {
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
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 9px;
            margin: 14px 0;
        }

        .card {
            min-height: 70px;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: linear-gradient(180deg, #fff, #f8fbfe);
        }

        .label {
            color: var(--muted);
            font-size: 10px;
        }

        .value {
            margin-top: 5px;
            color: var(--primary-dark);
            font-size: 12.5px;
            font-weight: 800;
        }

        .section {
            margin-top: 12px;
            padding: 13px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .section-title {
            margin: -13px -15px 12px;
            padding: 8px 13px;
            border-radius: 7px 7px 0 0;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 800;
        }

        .text-value {
            min-height: 35px;
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .feedback-section {
            border-color: #86b6e6;
            background: #f8fbfe;
        }

        .next-action {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 190px;
            gap: 15px;
            align-items: start;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 800;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: isolate;
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
            min-height: 65px;
            padding-top: 10px;
            border-top: 1px solid #4b6075;
            color: var(--primary-dark);
            font-weight: 700;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 26px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 9.5px;
        }

        @media screen and (max-width: 760px) {
            .follow-up-page {
                width: 100%;
                margin: 0;
                padding: 15px;
                box-shadow: none;
            }

            .company-document-layout,
            .summary-grid,
            .next-action,
            .signatures {
                grid-template-columns: 1fr !important;
            }

            .company-document-logo-wrap {
                justify-content: center !important;
            }
        }

        @media print {
            body {
                background: #fff;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .follow-up-page {
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
<main class="follow-up-page">
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
        document-title="متابعة عميل"
        :document-number="$followUp->follow_up_number"
        :document-date="$followUp->scheduled_at?->format('d/m/Y')"
    />

    @php
        $typeLabel = \App\Models\CustomerFollowUp::typeOptions()[$followUp->type]
            ?? $followUp->type;

        $statusLabel = $followUp->isOverdue()
            ? 'متأخرة'
            : (\App\Models\CustomerFollowUp::statusOptions()[$followUp->status]
                ?? $followUp->status);

        $priorityLabel = \App\Models\CustomerFollowUp::priorityOptions()[$followUp->priority]
            ?? $followUp->priority;

        $resultLabel = filled($followUp->result)
            ? (\App\Models\CustomerFollowUp::resultOptions()[$followUp->result]
                ?? $followUp->result)
            : '—';
    @endphp

    <section class="summary-grid">
        <div class="card">
            <div class="label">العميل</div>
            <div class="value">{{ $followUp->customer->name }}</div>
        </div>

        <div class="card">
            <div class="label">مسؤول العميل</div>
            <div class="value">
                {{ $followUp->contact_person ?: ($followUp->customer->contact_person ?: '—') }}
            </div>
        </div>

        <div class="card">
            <div class="label">مسؤول المبيعات</div>
            <div class="value">
                {{ $followUp->salesResponsible?->name ?: '—' }}
            </div>
        </div>

        <div class="card">
            <div class="label">نوع المتابعة</div>
            <div class="value">{{ $typeLabel }}</div>
        </div>

        <div class="card">
            <div class="label">الموعد</div>
            <div class="value ltr">
                {{ $followUp->scheduled_at?->format('d/m/Y h:i A') ?: '—' }}
            </div>
        </div>

        <div class="card">
            <div class="label">الحالة</div>
            <div class="value">
                <span class="status-badge">{{ $statusLabel }}</span>
            </div>
        </div>

        <div class="card">
            <div class="label">الأولوية</div>
            <div class="value">{{ $priorityLabel }}</div>
        </div>

        <div class="card">
            <div class="label">نتيجة المتابعة</div>
            <div class="value">{{ $resultLabel }}</div>
        </div>
    </section>

    <section class="section">
        <div class="section-title">موضوع المتابعة</div>
        <div class="text-value">{{ $followUp->subject }}</div>
    </section>

    <section class="section">
        <div class="section-title">ما تم مناقشته</div>
        <div class="text-value">{{ $followUp->discussion ?: '—' }}</div>
    </section>

    <section class="section feedback-section">
        <div class="section-title">رد العميل / الفيدباك</div>
        <div class="text-value">{{ $followUp->customer_feedback ?: '—' }}</div>
    </section>

    @if (filled($followUp->visit_location))
        <section class="section">
            <div class="section-title">مكان الزيارة</div>
            <div class="text-value">{{ $followUp->visit_location }}</div>
        </section>
    @endif

    <section class="section">
        <div class="section-title">الإجراء والمتابعة القادمة</div>

        <div class="next-action">
            <div>
                <div class="label">الإجراء المطلوب</div>
                <div class="text-value">
                    {{ $followUp->next_action ?: 'لا يوجد إجراء تالٍ مسجل.' }}
                </div>
            </div>

            <div>
                <div class="label">موعد المتابعة القادمة</div>
                <div class="value ltr">
                    {{ $followUp->next_follow_up_at?->format('d/m/Y h:i A') ?: '—' }}
                </div>
            </div>
        </div>
    </section>

    @if (filled($followUp->notes))
        <section class="section">
            <div class="section-title">ملاحظات داخلية</div>
            <div class="text-value">{{ $followUp->notes }}</div>
        </section>
    @endif

    <section class="signatures">
        <div class="signature">مسؤول المبيعات</div>
        <div class="signature">مدير المبيعات</div>
        <div class="signature">اعتماد الإدارة</div>
    </section>

    <footer class="footer">
        <span>
            أُنشئت بواسطة:
            {{ $followUp->creator?->name ?: '—' }}
        </span>

        <span>
            تاريخ الطباعة:
            <span class="ltr">{{ $printedAt->format('d/m/Y h:i A') }}</span>
        </span>

        <span>{{ $settings->commercialName() }}</span>
    </footer>
</main>
</body>
</html>
