<table class="due-modal-table">
    <thead>
    <tr>
        <th>رقم الفاتورة</th>
        <th>العميل / المورد</th>
        <th>تاريخ الفاتورة</th>
        <th>تاريخ الاستحقاق</th>
        <th>المتبقي</th>
        <th>عرض</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($records as $record)
        <tr>
            <td>{{ $record['document_number'] }}</td>
            <td>{{ $record['party_name'] }}</td>
            <td>{{ $record['invoice_date'] }}</td>
            <td>{{ $record['due_date'] }}</td>
            <td>{{ $money($record['remaining_amount']) }} ج.م</td>
            <td><a href="{{ $record['url'] }}">عرض الفاتورة</a></td>
        </tr>
    @empty
        <tr>
            <td colspan="6">لا توجد فواتير متأخرة.</td>
        </tr>
    @endforelse
    </tbody>
    <tfoot>
    <tr>
        <td colspan="4">الإجمالي</td>
        <td colspan="2">{{ $money($total) }} ج.م</td>
    </tr>
    </tfoot>
</table>
