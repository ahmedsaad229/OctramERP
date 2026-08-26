<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
</head>

<body>

@php
    $rows = $report['rows'] ?? [];
@endphp

<table border="1">

    <thead>
    <tr>
        <th>فاتورة الشراء</th>
        <th>تاريخ الشراء</th>
        <th>المورد</th>
        <th>كود الصنف</th>
        <th>اسم الصنف</th>
        <th>كمية الشراء</th>
        <th>الكمية المباعة</th>
        <th>المتبقي</th>
        <th>الحالة</th>
        <th>العملاء</th>
        <th>فواتير البيع</th>
    </tr>
    </thead>

    <tbody>

    @foreach ($rows as $row)

        <tr>

            <td>{{ $row['purchase_document'] }}</td>

            <td>{{ $row['purchase_date'] }}</td>

            <td>{{ $row['supplier'] }}</td>

            <td>{{ $row['item_code'] }}</td>

            <td>{{ $row['item_name'] }}</td>

            <td>
                {{ number_format($row['purchase_quantity'], 2, '.', '') }}
            </td>

            <td>
                {{ number_format($row['sold_quantity'], 2, '.', '') }}
            </td>

            <td>
                {{ number_format($row['remaining_quantity'], 2, '.', '') }}
            </td>

            <td>
                {{ $row['status_label'] }}
            </td>

            <td>
                {{ implode(' / ', $row['customers']) ?: '—' }}
            </td>

            <td>
                @foreach ($row['allocations'] as $sale)
                    {{ $sale['document_number'] }}
                    -
                    {{ $sale['customer'] }}
                    -
                    كمية {{ number_format($sale['quantity'], 2) }}
                    @if (!$loop->last)
                        |
                    @endif
                @endforeach
            </td>

        </tr>

    @endforeach

    </tbody>

</table>

</body>
</html>
