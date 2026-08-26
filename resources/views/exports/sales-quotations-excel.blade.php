<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
</head>

<body>

@php
    $totalSubtotal = (float) $quotations->sum('subtotal');
    $totalDiscount = (float) $quotations->sum('discount_amount');
    $totalTax = (float) $quotations->sum('tax_amount');
    $grandTotal = (float) $quotations->sum('total_amount');
@endphp

<table border="1">

    <thead>
    <tr>
        <th>رقم عرض السعر</th>
        <th>التاريخ</th>
        <th>صالح حتى</th>
        <th>العميل</th>
        <th>المخزن</th>
        <th>مسؤول المبيعات</th>
        <th>عدد الأصناف</th>
        <th>الإجمالي الفرعي</th>
        <th>الخصم</th>
        <th>الضريبة</th>
        <th>الإجمالي النهائي</th>
        <th>حالة التحويل</th>
        <th>صلاحية العرض</th>
    </tr>
    </thead>

    <tbody>

    @foreach ($quotations as $quotation)

        @php
            $conversionLabel = match ($quotation->conversionStatus()) {
                'fully_converted' => 'تم التحويل بالكامل',
                'partially_converted' => 'تم التحويل جزئيًا',
                default => 'لم يتم التحويل',
            };
        @endphp

        <tr>

            <td>{{ $quotation->quotation_number }}</td>

            <td>
                {{ $quotation->quotation_date?->format('d/m/Y') ?: '—' }}
            </td>

            <td>
                {{ $quotation->valid_until?->format('d/m/Y') ?: '—' }}
            </td>

            <td>
                {{ $quotation->customer?->name ?: '—' }}
            </td>

            <td>
                {{ $quotation->warehouse?->name ?: '—' }}
            </td>

            <td>
                {{ $quotation->salesResponsible?->name ?: '—' }}
            </td>

            <td>
                {{ $quotation->items_count }}
            </td>

            <td>
                {{ number_format((float) $quotation->subtotal, 2, '.', '') }}
            </td>

            <td>
                {{ number_format((float) $quotation->discount_amount, 2, '.', '') }}
            </td>

            <td>
                {{ number_format((float) $quotation->tax_amount, 2, '.', '') }}
            </td>

            <td>
                {{ number_format((float) $quotation->total_amount, 2, '.', '') }}
            </td>

            <td>
                {{ $conversionLabel }}
            </td>

            <td>
                {{ $quotation->expiryLabel() }}
            </td>

        </tr>

    @endforeach

    <tr>
        <td colspan="7">
            <strong>الإجماليات</strong>
        </td>

        <td>
            <strong>{{ number_format($totalSubtotal, 2, '.', '') }}</strong>
        </td>

        <td>
            <strong>{{ number_format($totalDiscount, 2, '.', '') }}</strong>
        </td>

        <td>
            <strong>{{ number_format($totalTax, 2, '.', '') }}</strong>
        </td>

        <td>
            <strong>{{ number_format($grandTotal, 2, '.', '') }}</strong>
        </td>

        <td colspan="2"></td>
    </tr>

    </tbody>

</table>

</body>
</html>
