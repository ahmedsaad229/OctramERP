<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
</head>

<body>

<table border="1">
    <thead>
    <tr>
        <th>رقم المستند</th>
        <th>رقم فاتورة المورد</th>
        <th>التاريخ</th>
        <th>المورد</th>
        <th>أمر التوريد</th>
        <th>طلب الشراء</th>
        <th>الإجمالي النهائي</th>
        <th>قيمة الضريبة</th>
        <th>نوع التعامل</th>
        <th>تاريخ الاستحقاق</th>
        <th>حالة الاستحقاق</th>
        <th>المخزن</th>
        <th>عدد الأصناف</th>
    </tr>
    </thead>

    <tbody>

    @foreach ($invoices as $invoice)
        <tr>
            <td>{{ $invoice->code }}</td>

            <td>
                {{ $invoice->invoice_number ?: '—' }}
            </td>

            <td>
                {{ $invoice->invoice_date?->format('d/m/Y') }}
            </td>

            <td>
                {{ $invoice->supplier?->name ?: '—' }}
            </td>

            <td>
                {{ $invoice->supplierPurchaseOrder?->code ?: '—' }}
            </td>

            <td>
                {{ $invoice->supplierPurchaseOrder?->purchaseRequest?->code ?: '—' }}
            </td>

            <td>
                {{ number_format((float) $invoice->totalAmount(), 2, '.', '') }}
            </td>

            <td>
                {{ number_format((float) $invoice->tax_amount, 2, '.', '') }}
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
                {{ $invoice->warehouse?->name ?: '—' }}
            </td>

            <td>
                {{ $invoice->items_count }}
            </td>
        </tr>
    @endforeach

    <tr>
        <td colspan="6">
            <strong>الإجمالي</strong>
        </td>

        <td>
            <strong>
                {{ number_format(
                    (float) $invoices->sum(
                        fn ($invoice) => $invoice->totalAmount()
                    ),
                    2,
                    '.',
                    ''
                ) }}
            </strong>
        </td>

        <td>
            <strong>
                {{ number_format(
                    (float) $invoices->sum('tax_amount'),
                    2,
                    '.',
                    ''
                ) }}
            </strong>
        </td>

        <td colspan="5"></td>
    </tr>

    </tbody>
</table>

</body>
</html>
