<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 landscape; margin: 9mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font: 11px Arial, Tahoma, sans-serif; }
        .toolbar { margin-bottom: 12px; }
        .toolbar button { border: 0; border-radius: 6px; padding: 8px 16px; background: #2563eb; color: white; cursor: pointer; }
        header { display: flex; align-items: end; justify-content: space-between; border-bottom: 2px solid #374151; padding-bottom: 10px; }
        h1 { margin: 0; font-size: 20px; }
        .meta { color: #6b7280; }
        .summary { display: flex; gap: 10px; margin: 12px 0; }
        .summary div { min-width: 180px; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: center; }
        th { background: #f3f4f6; }
        .ltr { direction: ltr; unicode-bidi: isolate; }
        .empty { padding: 40px; text-align: center; color: #6b7280; }
        @media print { .toolbar { display: none; } }
    </style>
    @include('print.partials.report-style')
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">طباعة / حفظ PDF</button></div>
    <header>
        <h1>{{ $title }}</h1>
        <div class="meta">تاريخ الطباعة: <span class="ltr">{{ $printedAt->format('d/m/Y H:i') }}</span></div>
    </header>

    @php
        $quantity = fn ($value): string => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
        $money = fn ($value): string => number_format((float) $value, 2).' ج.م';
    @endphp

    @if ($type === 'balances')
        <div class="summary">
            <div>إجمالي الكمية: <strong class="ltr">{{ $quantity($report['total_quantity']) }}</strong></div>
            <div>إجمالي قيمة المخزون: <strong class="ltr">{{ $money($report['total_value']) }}</strong></div>
        </div>
        @php($headers = ['كود الصنف','اسم الصنف','الفئة','المخزن','الوحدة','الكمية الحالية','متوسط التكلفة','قيمة المخزون'])
    @elseif ($type === 'movements')
        @php($headers = ['التاريخ','نوع الحركة','رقم المستند','الصنف','المخزن','وارد','صادر','الرصيد بعد الحركة','التكلفة'])
    @else
        @php($headers = ['كود الصنف','الصنف','الفئة','المخزن','الرصيد الحالي','حد إعادة الطلب','الفرق','الحالة'])
    @endif

    @if ($report['rows']->isEmpty())
        <div class="empty">{{ $type === 'balances' ? 'لا توجد أرصدة.' : ($type === 'movements' ? 'لا توجد حركات.' : 'لا توجد أصناف منخفضة الرصيد.') }}</div>
    @else
        <table>
            <thead><tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach ($report['rows'] as $row)
                <tr>
                    @if ($type === 'balances')
                        <td class="ltr">{{ $row['item_code'] }}</td><td>{{ $row['item_name'] }}</td><td>{{ $row['category'] }}</td>
                        <td>{{ $row['warehouse'] }}</td><td>{{ $row['unit'] }}</td><td class="ltr">{{ $quantity($row['quantity']) }}</td>
                        <td class="ltr">{{ $money($row['average_cost']) }}</td><td class="ltr">{{ $money($row['inventory_value']) }}</td>
                    @elseif ($type === 'movements')
                        <td class="ltr">{{ $row['date'] }}</td><td>{{ $row['type_label'] }}</td><td class="ltr">{{ $row['reference'] }}</td>
                        <td>{{ $row['item'] }}</td><td>{{ $row['warehouse'] }}</td>
                        <td class="ltr">{{ $row['inbound'] > 0 ? $quantity($row['inbound']) : '—' }}</td>
                        <td class="ltr">{{ $row['outbound'] > 0 ? $quantity($row['outbound']) : '—' }}</td>
                        <td class="ltr">{{ $quantity($row['running_balance']) }}</td><td class="ltr">{{ $money($row['unit_cost']) }}</td>
                    @else
                        <td class="ltr">{{ $row['item_code'] }}</td><td>{{ $row['item_name'] }}</td><td>{{ $row['category'] }}</td>
                        <td>{{ $row['warehouse'] }}</td><td class="ltr">{{ $quantity($row['quantity']) }}</td>
                        <td class="ltr">{{ $quantity($row['reorder_level']) }}</td><td class="ltr">{{ $quantity($row['difference']) }}</td>
                        <td>{{ $row['status_label'] }}</td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
