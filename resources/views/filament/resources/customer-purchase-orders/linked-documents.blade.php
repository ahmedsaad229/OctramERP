@if(empty($documents))
    <div class="py-8 text-center text-gray-500">لم يتم إصدار فواتير بيع مرتبطة بهذا الأمر بعد.</div>
@else
    <div class="space-y-4">
        @foreach($documents as $document)
            <details class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                <summary class="cursor-pointer font-semibold">
                    {{ $document['reference'] }} — {{ $document['date'] }} — الكمية المنفذة: {{ $document['quantity'] }}
                </summary>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div>العميل: {{ $document['customer'] }}</div>
                    <div>إجمالي الفاتورة: <span dir="ltr">{{ number_format($document['total'],2) }} ج.م</span></div>
                    <div>عدد البنود: {{ $document['lines'] }}</div>
                    <div>تاريخ إنشاء الربط: {{ $document['linkedAt'] }}</div>
                </div>
                @if($document['url'])<a class="mt-3 inline-block text-primary-600 hover:underline" href="{{ $document['url'] }}">فتح الفاتورة</a>@endif
                <div class="mt-4 overflow-x-auto"><table class="w-full text-sm">
                    <thead><tr class="border-b"><th class="p-2">الصنف</th><th>المطلوب</th><th>المنفذ بهذه الفاتورة</th><th>المنفذ سابقًا</th><th>المتبقي بعدها</th></tr></thead>
                    <tbody>@foreach($document['details'] as $detail)<tr class="border-b border-gray-100"><td class="p-2">{{ $detail['item'] }}</td><td class="text-center">{{ $detail['ordered'] }}</td><td class="text-center">{{ $detail['executed'] }}</td><td class="text-center">{{ $detail['previous'] }}</td><td class="text-center">{{ $detail['remainingAfter'] }}</td></tr>@endforeach</tbody>
                </table></div>
            </details>
        @endforeach
    </div>
@endif
