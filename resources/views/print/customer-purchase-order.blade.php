<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>أمر توريد {{ $order->document_number }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        :root {
            --primary: #123b67;
            --primary-dark: #0d2f54;
            --border: #bfd0e2;
            --text: #172033;
            --muted: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef3f8;
            color: var(--text);
            font-family: Arial, Tahoma, sans-serif;
            line-height: 1.5;
        }

        .order-page {
            width: min(297mm, calc(100% - 30px));
            min-height: 194mm;
            margin: 20px auto;
            padding: 8mm;
            background: #fff;
            box-shadow: 0 8px 28px rgb(15 23 42 / 12%);
            font-size: 11px;
        }

        .toolbar {
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

        .company-document-header {
            margin-bottom: 18px;
            padding: 15px 17px;
            border: 1px solid var(--border);
            border-top: 5px solid var(--primary);
            border-radius: 10px;
            background: #fff;
        }

        .company-document-layout {
            display: grid;
            grid-template-columns:
                minmax(190px, .8fr)
                minmax(310px, 1.5fr)
                minmax(150px, .65fr);
            align-items: center;
            gap: 20px;
        }

        .company-document-title {
            display: inline-block;
            margin-bottom: 10px;
            padding: 8px 18px;
            border-radius: 7px;
            background: var(--primary);
            color: #fff;
            font-size: 22px;
            font-weight: 800;
        }

        .company-document-meta {
            display: grid;
            gap: 5px;
        }

        .company-document-meta > div {
            display: grid;
            grid-template-columns: 85px 1fr;
            gap: 7px;
        }

        .company-document-meta span {
            color: var(--muted);
        }

        .company-document-company {
            text-align: center;
        }

        .company-document-company-name {
            color: var(--primary);
            font-size: 20px;
            font-weight: 800;
        }

        .company-document-activity {
            margin-top: 3px;
            color: #475569;
            font-weight: 700;
        }

        .company-document-contact-lines {
            margin-top: 8px;
            color: #475569;
            font-size: 10.5px;
            line-height: 1.65;
        }

        .company-document-legal {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
        }

        .company-document-logo-wrap {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            direction: ltr;
        }

        .company-document-logo {
            max-width: 150px;
            max-height: 90px;
            object-fit: contain;
        }

        .company-document-logo-placeholder {
            display: grid;
            place-items: center;
            width: 130px;
            height: 72px;
            border: 1px dashed #94a3b8;
            border-radius: 8px;
            color: var(--primary);
            font-size: 18px;
            font-weight: 800;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 9px;
            margin: 15px 0;
        }

        .meta-card {
            min-height: 68px;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: linear-gradient(180deg, #fff, #f8fbfe);
        }

        .meta-label {
            margin-bottom: 5px;
            color: var(--muted);
            font-size: 10px;
        }

        .meta-value {
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 800;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: isolate;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        thead {
            display: table-header-group;
        }

        th {
            padding: 8px 6px;
            border-left: 1px solid #7796b5;
            background: var(--primary);
            color: #fff;
            text-align: center;
            white-space: nowrap;
        }

        td {
            padding: 8px 6px;
            border-left: 1px solid #d7e1eb;
            border-bottom: 1px solid #d7e1eb;
            vertical-align: middle;
            text-align: center;
        }

        th:last-child,
        td:last-child {
            border-left: 0;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        tbody tr:nth-child(even) {
            background: #f8fbfe;
        }

        tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .notes-card {
            margin-top: 14px;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .notes-title {
            margin-bottom: 6px;
            color: var(--primary);
            font-weight: 800;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 100px;
            margin-top: 38px;
            padding: 0 55px;
        }

        .signature {
            min-height: 58px;
            padding-top: 10px;
            border-top: 1px solid #4b6075;
            color: var(--primary-dark);
            font-weight: 700;
            text-align: center;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 22px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 10px;
        }

        @media print {
            body {
                background: #fff;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .order-page {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .toolbar {
                display: none !important;
            }
        }
    </style>
</head>

<body>
<main class="order-page">
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
        :settings="$settings"
        document-title="أمر توريد"
        :document-number="$order->document_number"
        :document-date="$order->order_date?->format('d/m/Y')"
    />

    <section class="meta-grid">
        <div class="meta-card">
            <div class="meta-label">رقم أمر العميل</div>
            <div class="meta-value ltr">
                {{ $order->customer_order_number ?: '—' }}
            </div>
        </div>

        <div class="meta-card">
            <div class="meta-label">العميل</div>
            <div class="meta-value">
                {{ $order->customer->name }}
            </div>
        </div>

        <div class="meta-card">
            <div class="meta-label">المشروع</div>
            <div class="meta-value">
                {{ $order->project_name ?: '—' }}
            </div>
        </div>

        <div class="meta-card">
            <div class="meta-label">الحالة</div>
            <div class="meta-value">
                {{
                    \App\Models\CustomerPurchaseOrder::statusOptions()[
                        $order->status
                    ] ?? $order->status
                }}
            </div>
        </div>

        <div class="meta-card">
            <div class="meta-label">تاريخ الاستلام</div>
            <div class="meta-value ltr">
                {{ $order->received_date?->format('d/m/Y') ?: '—' }}
            </div>
        </div>

        <div class="meta-card">
            <div class="meta-label">التسليم المطلوب</div>
            <div class="meta-value ltr">
                {{
                    $order->required_delivery_date?->format('d/m/Y')
                    ?: '—'
                }}
            </div>
        </div>

        <div class="meta-card">
            <div class="meta-label">مكان التسليم</div>
            <div class="meta-value">
                {{ $order->delivery_location ?: '—' }}
            </div>
        </div>

        <div class="meta-card">
            <div class="meta-label">نسبة التنفيذ</div>
            <div class="meta-value ltr">
                {{ number_format((float) $order->execution_percentage, 2) }}%
            </div>
        </div>
    </section>

    <table aria-label="أصناف أمر التوريد">
        <thead>
        <tr>
            <th>م</th>
            <th>الصنف</th>
            <th>الوحدة</th>
            <th>المطلوب</th>
            <th>المنفذ</th>
            <th>المتبقي</th>
            <th>سعر الوحدة</th>
            <th>الإجمالي</th>
            <th>ملاحظات</th>
        </tr>
        </thead>

        <tbody>
        @forelse ($order->items as $line)
            <tr>
                <td class="ltr">{{ $loop->iteration }}</td>
                <td>{{ $line->item->name }}</td>
                <td>{{ $line->unit?->name ?: '—' }}</td>
                <td class="ltr">{{ $line->ordered_quantity }}</td>
                <td class="ltr">{{ $line->executed_quantity }}</td>
                <td class="ltr">{{ $line->remaining_quantity }}</td>
                <td class="ltr">
                    {{ number_format((float) $line->unit_price, 2) }} ج.م
                </td>
                <td class="ltr">
                    <strong>
                        {{ number_format((float) $line->line_total, 2) }}
                        ج.م
                    </strong>
                </td>
                <td>{{ $line->notes ?: '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" style="padding: 25px;">
                    لا توجد أصناف في أمر التوريد.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <section class="notes-card">
        <div class="notes-title">الملاحظات</div>
        <div>{{ $order->notes ?: 'لا توجد ملاحظات.' }}</div>
    </section>

    @if (! empty($executionDocuments))
        <section style="margin-top: 16px;">
            <h3 style="color: #123b67;">
                ملخص مستندات التنفيذ
            </h3>

            <table>
                <thead>
                <tr>
                    <th>رقم فاتورة البيع</th>
                    <th>التاريخ</th>
                    <th>الكمية المنفذة</th>
                    <th>قيمة الفاتورة</th>
                </tr>
                </thead>

                <tbody>
                @foreach ($executionDocuments as $document)
                    <tr>
                        <td class="ltr">
                            {{ $document['reference'] }}
                        </td>
                        <td class="ltr">
                            {{ $document['date'] }}
                        </td>
                        <td class="ltr">
                            {{ $document['quantity'] }}
                        </td>
                        <td class="ltr">
                            {{
                                number_format(
                                    (float) $document['total'],
                                    2
                                )
                            }}
                            ج.م
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <section class="signatures">
        <div class="signature">إعداد أمر التوريد</div>
        <div class="signature">اعتماد الشركة</div>
    </section>

    <footer class="footer">
        <span dir="ltr">{{ $order->document_number }}</span>
        <span>
            أمر توريد صادر من {{ $settings->commercialName() }}
        </span>
    </footer>
</main>
</body>
</html>
