<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $documentTitle }} {{ $documentNumber }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 9mm;
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
            background: #eef2f7;
            color: var(--text);
            font-family: Arial, Tahoma, sans-serif;
            line-height: 1.5;
        }

        .document-page {
            width: min(210mm, calc(100% - 30px));
            min-height: 279mm;
            margin: 20px auto;
            padding: 9mm;
            background: var(--white);
            box-shadow: 0 8px 28px rgb(15 23 42 / 12%);
            font-size: 10.5px;
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

        .company-document-header {
            margin-bottom: 17px !important;
            padding: 15px 17px !important;
            border: 1px solid var(--border) !important;
            border-top: 5px solid var(--primary) !important;
            border-radius: 10px !important;
            background: #fff !important;
        }

        .company-document-layout {
            display: grid !important;
            grid-template-columns:
                minmax(185px, .8fr)
                minmax(285px, 1.5fr)
                minmax(135px, .65fr) !important;
            align-items: center !important;
            gap: 18px !important;
            direction: rtl !important;
        }

        .company-document-title {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            width: 100% !important;
            min-height: 47px !important;
            margin-bottom: 8px !important;
            padding: 8px 12px !important;
            border-radius: 8px !important;
            background: var(--primary) !important;
            color: #fff !important;
            font-size: 20px !important;
            font-weight: 800 !important;
            text-align: center !important;
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

        .company-document-logo-wrap {
            display: flex !important;
            justify-content: flex-start !important;
            align-items: center !important;
            justify-self: start !important;
            width: 100% !important;
            direction: ltr !important;
        }

        .company-document-logo {
            max-width: 135px !important;
            max-height: 82px !important;
            object-fit: contain !important;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 9px;
            margin: 14px 0;
        }

        .summary-card {
            min-height: 68px;
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
            font-size: 12px;
            font-weight: 800;
        }

        .ltr,
        .numeric {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
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

        th,
        td {
            padding: 7px 5px;
            border-left: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            text-align: center;
            overflow-wrap: anywhere;
        }

        th:last-child,
        td:last-child {
            border-left: 0;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        th {
            background: var(--primary);
            color: #fff;
            font-weight: 800;
        }

        tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .description {
            text-align: right;
        }

        .totals-wrap {
            display: flex;
            justify-content: flex-start;
            margin-top: 14px;
            break-inside: avoid;
        }

        .totals {
            width: min(100%, 370px);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 8px 11px;
            border-bottom: 1px solid var(--border);
        }

        .total-row:last-child {
            border-bottom: 0;
        }

        .grand-total {
            background: var(--primary);
            color: #fff;
            font-size: 14px;
            font-weight: 800;
        }

        .notes {
            margin-top: 15px;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            white-space: pre-line;
            break-inside: avoid;
        }

        .notes-title {
            margin-bottom: 6px;
            color: var(--primary-dark);
            font-weight: 800;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 45px;
            margin-top: 45px;
            padding: 0 20px;
            text-align: center;
        }

        .signature {
            min-height: 60px;
            padding-top: 10px;
            border-top: 1px solid #4b6075;
            color: var(--primary-dark);
            font-weight: 700;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 25px;
            padding-top: 9px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 9px;
        }

        @media screen and (max-width: 760px) {
            .document-page {
                width: 100%;
                margin: 0;
                padding: 14px;
                box-shadow: none;
            }

            .company-document-layout,
            .summary,
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

            .document-page {
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
<main class="document-page">
    <div class="toolbar">
        <button type="button" class="print-button" onclick="window.print()">
            طباعة / حفظ PDF
        </button>
    </div>

    <x-company-document-header
        :settings="$settings"
        :document-title="$documentTitle"
        :document-number="$documentNumber"
        :document-date="$documentDate"
    />

    <section class="summary">
        <div class="summary-card">
            <div class="label">{{ $partyLabel }}</div>
            <div class="value">{{ $partyName }}</div>
        </div>

        <div class="summary-card">
            <div class="label">رقم المستند</div>
            <div class="value ltr">{{ $documentNumber }}</div>
        </div>

        <div class="summary-card">
            <div class="label">التاريخ</div>
            <div class="value ltr">{{ $documentDate }}</div>
        </div>

        @foreach ($meta as $label => $value)
            <div class="summary-card">
                <div class="label">{{ $label }}</div>
                <div class="value">{{ filled($value) ? $value : '—' }}</div>
            </div>
        @endforeach
    </section>

    @php
        $readFirst = static function ($object, array $fields, mixed $default = null) {
            foreach ($fields as $field) {
                $value = data_get($object, $field);

                if ($value !== null && $value !== '') {
                    return $value;
                }
            }

            return $default;
        };

        $subtotal = 0.0;

        /*
         * إخفاء القيم الصفرية في طباعة طلب الشراء فقط.
         * باقي مستندات المشتريات تظل بدون تغيير.
         */
        $hideZeroValues = trim((string) $documentTitle) === 'طلب شراء';

        // إظهار ضريبة كل بند في أمر توريد المورد فقط.
        $showItemTax = trim((string) $documentTitle) === 'أمر توريد';

        // الضريبة محفوظة على رأس أمر التوريد، لذلك يتم توزيعها على البنود
        // بنسبة قيمة كل بند، مع ضبط آخر بند لضمان تطابق المجموع 100%.
        $documentTaxForItems = (float) ($document->tax_amount ?? 0);

        $itemsSubtotalForTax = (float) $items->sum(function ($item) use ($readFirst) {
            $quantity = (float) $readFirst(
                $item,
                ['ordered_quantity', 'quantity', 'requested_quantity'],
                0
            );

            $unitPrice = (float) $readFirst(
                $item,
                ['unit_price', 'price'],
                0
            );

            return (float) $readFirst(
                $item,
                ['line_total', 'total', 'line_subtotal'],
                $quantity * $unitPrice
            );
        });

        $allocatedItemTax = 0.0;

        $printNumber = static function (
            float|int|string|null $value,
            int $decimals = 2,
            string $suffix = ''
        ) use ($hideZeroValues): string {
            $number = (float) ($value ?? 0);

            if ($hideZeroValues && abs($number) < 0.00001) {
                return '';
            }

            return number_format($number, $decimals).$suffix;
        };
    @endphp

    @if ($items->isNotEmpty())
        <table aria-label="أصناف المستند">
            <colgroup>
                @if ($showItemTax)
                    <col style="width: 5%">
                    <col style="width: 12%">
                    <col style="width: 24%">
                    <col style="width: 9%">
                    <col style="width: 9%">
                    <col style="width: 13%">
                    <col style="width: 13%">
                    <col style="width: 15%">
                @else
                    <col style="width: 5%">
                    <col style="width: 14%">
                    <col style="width: 27%">
                    <col style="width: 10%">
                    <col style="width: 11%">
                    <col style="width: 14%">
                    <col style="width: 19%">
                @endif
            </colgroup>

            <thead>
            <tr>
                <th>م</th>
                <th>كود الصنف</th>
                <th>بيان الصنف</th>
                <th>الوحدة</th>
                <th>الكمية</th>
                <th>سعر الوحدة</th>
                @if ($showItemTax)
                    <th>الضريبة</th>
                @endif
                <th>الإجمالي</th>
            </tr>
            </thead>

            <tbody>
            @foreach ($items as $item)
                @php
                    $quantity = (float) $readFirst(
                        $item,
                        $quantityFields,
                        0
                    );

                    $unitPrice = (float) $readFirst(
                        $item,
                        $priceFields,
                        0
                    );

                    $lineTotal = (float) $readFirst(
                        $item,
                        ['line_total', 'total', 'line_subtotal'],
                        $quantity * $unitPrice
                    );

                    $subtotal += $lineTotal;

                    $itemTax = 0.0;

                    if ($showItemTax && abs($documentTaxForItems) > 0.00001 && $itemsSubtotalForTax > 0) {
                        if ($loop->last) {
                            // آخر بند يأخذ فرق التقريب حتى يساوي مجموع البنود ضريبة المستند.
                            $itemTax = round($documentTaxForItems - $allocatedItemTax, 2);
                        } else {
                            $itemTax = round(
                                $documentTaxForItems * ($lineTotal / $itemsSubtotalForTax),
                                2
                            );

                            $allocatedItemTax += $itemTax;
                        }
                    }
                @endphp

                <tr>
                    <td class="numeric">{{ $loop->iteration }}</td>

                    <td class="numeric">
                        {{ data_get($item, 'item.code', '—') }}
                    </td>

                    <td class="description">
                        {{ data_get($item, 'item.name')
                            ?? data_get($item, 'description')
                            ?? '—' }}
                    </td>

                    <td>
                        {{ data_get($item, 'unit.name', '—') }}
                    </td>

                    <td class="numeric">
                        {{ $hideZeroValues && abs($quantity) < 0.00001 ? '' : \App\Support\QuantityFormatter::formatForDisplay($quantity) }}
                    </td>

                    <td class="numeric">
                        {{ $printNumber($unitPrice, 2, ' ج.م') }}
                    </td>

                    @if ($showItemTax)
                        <td class="numeric">
                            {{ $printNumber($itemTax, 2, ' ج.م') }}
                        </td>
                    @endif

                    <td class="numeric">
                        <strong>{{ $printNumber($lineTotal, 2, ' ج.م') }}</strong>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="totals-wrap">
            <section class="totals">
                <div class="total-row">
                    <span>الإجمالي قبل الضريبة</span>
                    <span class="numeric">
                        {{ $printNumber($subtotal, 2, ' ج.م') }}
                    </span>
                </div>

                @php
                    $discount = (float) (
                        $document->discount_amount ?? 0
                    );

                    $tax = (float) (
                        $document->tax_amount ?? 0
                    );

                    $grandTotal = (float) (
                        $document->total_amount
                        ?? max(0, $subtotal - $discount + $tax)
                    );
                @endphp

                <div class="total-row">
                    <span>الخصم</span>
                    <span class="numeric">
                        {{ $printNumber($discount, 2, ' ج.م') }}
                    </span>
                </div>

                <div class="total-row">
                    <span>الضريبة</span>
                    <span class="numeric">
                        {{ $printNumber($tax, 2, ' ج.م') }}
                    </span>
                </div>

                <div class="total-row grand-total">
                    <span>الإجمالي الكلي</span>
                    <span class="numeric">
                        {{ $printNumber($grandTotal, 2, ' ج.م') }}
                    </span>
                </div>
            </section>
        </div>
    @elseif ($amount !== null)
        <div class="totals-wrap">
            <section class="totals">
                <div class="total-row grand-total">
                    <span>قيمة السند</span>
                    <span class="numeric">
                        {{ $printNumber((float) $amount, 2, ' ج.م') }}
                    </span>
                </div>
            </section>
        </div>
    @endif

    <section class="notes">
        <div class="notes-title">البيان / الملاحظات</div>
        <div>{{ filled($notes) ? $notes : '—' }}</div>
    </section>

    <section class="signatures">
        <div class="signature">إعداد المستند</div>
        <div class="signature">المراجعة</div>
        <div class="signature">الاعتماد</div>
    </section>

    <footer class="footer">
        <span>{{ $settings->commercialName() }}</span>

        <span>
            تاريخ الطباعة:
            <span class="ltr">{{ $printedAt->format('d/m/Y h:i A') }}</span>
        </span>

        <span class="ltr">{{ $documentNumber }}</span>
    </footer>
</main>
</body>
</html>