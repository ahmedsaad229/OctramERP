<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>متابعة أوامر توريد العملاء</title>
    <style>
        @page{size:A4 landscape;margin:8mm}*{box-sizing:border-box}body{font-family:Arial,Tahoma,sans-serif;font-size:9px;color:#111827}
        .toolbar{margin-bottom:10px}.toolbar button{padding:7px 14px}h1{font-size:18px;margin:0 0 8px}table{width:100%;border-collapse:collapse}
        thead{display:table-header-group}tr{break-inside:avoid}th,td{border:1px solid #d1d5db;padding:4px;text-align:center}th{background:#f3f4f6}.ltr{direction:ltr}
        @media print{.toolbar{display:none}}
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">طباعة / حفظ PDF</button></div>
    <h1>متابعة أوامر توريد العملاء</h1>
    <p>تاريخ الطباعة: <span class="ltr">{{ $printedAt->format('d/m/Y H:i') }}</span></p>
    <table>
        <thead><tr>
            @foreach (['رقم المستند','رقم أمر العميل','العميل','المشروع','تاريخ الأمر','تاريخ التسليم','الحالة','نسبة التنفيذ','الأصناف','المكتملة','المتبقية','الكمية المتبقية','الفواتير','المرفقات','التأخير'] as $heading)
                <th>{{ $heading }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @forelse ($report['rows'] as $row)
            <tr>
                <td class="ltr">{{ $row['documentNumber'] }}</td><td>{{ $row['customerOrderNumber'] }}</td>
                <td>{{ $row['customer'] }}</td><td>{{ $row['project'] }}</td><td class="ltr">{{ $row['orderDate'] }}</td>
                <td class="ltr">{{ $row['deliveryDate'] }}</td><td>{{ $row['statusLabel'] }}</td><td>{{ number_format($row['percentage'], 2) }}%</td>
                <td>{{ $row['itemsCount'] }}</td><td>{{ $row['completedItems'] }}</td><td>{{ $row['remainingItems'] }}</td>
                <td>{{ $row['remainingQuantity'] }}</td><td>{{ $row['invoiceCount'] }}</td><td>{{ $row['attachmentCount'] }}</td>
                <td>{{ $row['delayed'] ? 'متأخر' : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="15">لا توجد أوامر توريد مطابقة لمعايير البحث.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
