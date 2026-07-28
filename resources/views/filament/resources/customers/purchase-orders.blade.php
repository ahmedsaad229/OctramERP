@if(!$summary || $summary['rows']->isEmpty())
    <div class="py-8 text-center text-gray-500">لا توجد أوامر توريد مسجلة لهذا العميل.</div>
@else
    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach(['total'=>'إجمالي أوامر التوريد','open'=>'الأوامر المفتوحة','delayed'=>'الأوامر المتأخرة','completed'=>'الأوامر المكتملة'] as $key=>$label)
            <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10"><div class="text-xs text-gray-500">{{ $label }}</div><div class="mt-1 text-xl font-bold">{{ $summary[$key] }}</div></div>
        @endforeach
    </div>
    <div class="overflow-x-auto"><table class="w-full min-w-[1100px] text-sm"><thead><tr class="border-b">
        @foreach(['رقم المستند','رقم أمر العميل','تاريخ الأمر','المشروع','تاريخ التسليم','الحالة','نسبة التنفيذ','عدد الأصناف','الكمية المتبقية','المرفقات','الفواتير','متأخر'] as $h)<th class="p-2">{{ $h }}</th>@endforeach
    </tr></thead><tbody>@foreach($summary['rows'] as $row)<tr class="border-b border-gray-100">
        <td class="p-2"><a class="text-primary-600 hover:underline" href="{{ $row['url'] }}">{{ $row['documentNumber'] }}</a></td><td>{{ $row['customerOrderNumber'] }}</td><td>{{ $row['orderDate'] }}</td><td>{{ $row['project'] }}</td><td>{{ $row['deliveryDate'] }}</td><td>{{ $row['statusLabel'] }}</td><td>{{ number_format($row['percentage'],2) }}%</td><td>{{ $row['itemsCount'] }}</td><td>{{ $row['remainingQuantity'] }}</td><td>{{ $row['attachmentCount'] }}</td><td>{{ $row['invoiceCount'] }}</td><td>{{ $row['delayed']?'متأخر':'—' }}</td>
    </tr>@endforeach</tbody></table></div>
@endif
