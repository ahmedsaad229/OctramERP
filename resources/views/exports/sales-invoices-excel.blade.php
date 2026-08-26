<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
</head>

<body>

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

<table border="1">

    <thead>
    <tr>
        <th>رقم الفاتورة</th>
        <th>الرقم الإلكتروني</th>
        <th>التاريخ</th>
        <th>العميل</th>
        <th>أمر التوريد</th>
        <th>الإجمالي النهائي</th>
        <th>قيمة الضريبة</th>
        <th>المسدد</th>
        <th>المتبقي</th>
        <th>نوع التعامل</th>
        <th>تاريخ الاستحقاق</th>
        <th>حالة الاستحقاق</th>
        <th>المخزن</th>
        <th>عدد الأصناف</th>
    </tr>
    </thead>

    <tbody>

    @foreach ($invoices as $invoice)

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

            <td>
                {{ $invoice->customer?->name ?: '—' }}
            </td>

            <td>
                {{ $invoice->customerPurchaseOrder?->code ?: '—' }}
            </td>

            <td>
                {{ number_format(
                    (float) $invoice->totalAmount(),
                    2,
                    '.',
                    ''
                ) }}
            </td>

            <td>
                {{ number_format(
                    (float) $invoice->tax_amount,
                    2,
                    '.',
                    ''
                ) }}
            </td>

            <td>
                {{ number_format(
                    (float) $invoice->paidAmount(),
                    2,
                    '.',
                    ''
                ) }}
            </td>

            <td>
                {{ number_format(
                    (float) $invoice->remainingAmount(),
                    2,
                    '.',
                    ''
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
                {{ $invoice->warehouse?->name ?: '—' }}
            </td>

            <td>
                {{ $invoice->items_count }}
            </td>

        </tr>

    @endforeach

    <tr>
        <td colspan="5">
            <strong>الإجماليات</strong>
        </td>

        <td>
            <strong>
                {{ number_format($totalInvoices, 2, '.', '') }}
            </strong>
        </td>

        <td>
            <strong>
                {{ number_format($totalTax, 2, '.', '') }}
            </strong>
        </td>

        <td>
            <strong>
                {{ number_format($totalPaid, 2, '.', '') }}
            </strong>
        </td>

        <td>
            <strong>
                {{ number_format($totalRemaining, 2, '.', '') }}
            </strong>
        </td>

        <td colspan="5"></td>
    </tr>

    </tbody>

</table>

</body>
</html>
