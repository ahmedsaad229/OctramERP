<x-filament-panels::page>
    <div class="octram-report space-y-6" dir="rtl">
    <form wire:submit="runReport" class="space-y-4">
        {{ $this->form }}
        <x-reports.actions :excel-url="$this->excelUrl()" :print-url="$this->printUrl()" />
    </form>

    @php
        $cards=['total'=>'إجمالي الأوامر','new'=>'أوامر جديدة','inProgress'=>'قيد التنفيذ','partial'=>'منفذة جزئيًا','completed'=>'منفذة بالكامل','delayed'=>'أوامر متأخرة','dueSoon'=>'تسليم خلال 7 أيام','suspended'=>'أوامر متوقفة','cancelled'=>'أوامر ملغاة'];
        $views=['all'=>'الكل','delayed'=>'متأخر','due_soon'=>'تسليم خلال 7 أيام','partial'=>'منفذ جزئيًا','new'=>'جديد','completed'=>'مكتمل','suspended'=>'متوقف'];
    @endphp
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        @foreach($cards as $key=>$label)
            <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10"><div class="text-sm text-gray-500">{{ $label }}</div><div class="mt-2 text-2xl font-bold">{{ $report['summary'][$key] }}</div></div>
        @endforeach
    </div>
    <div class="flex flex-wrap gap-2">
        @foreach($views as $key=>$label)<x-filament::button size="sm" :color="$activeView===$key?'primary':'gray'" wire:click="setView('{{ $key }}')">{{ $label }}</x-filament::button>@endforeach
    </div>
    <x-filament::section heading="أوامر التوريد">
        @if($report['rows']->isEmpty())
            <div class="py-10 text-center text-gray-500">لا توجد أوامر توريد مطابقة لمعايير البحث.</div>
        @else
            <x-reports.table min-width="110rem"><thead><tr class="text-gray-500">
                @foreach(['رقم المستند','رقم أمر العميل','العميل','المشروع','تاريخ الأمر','تاريخ التسليم المطلوب','الحالة','نسبة التنفيذ','عدد الأصناف','المكتملة','المتبقية','الكمية المتبقية','الفواتير','المرفقات','متأخر','الأيام'] as $h)<th class="whitespace-nowrap px-3 py-3 text-center">{{ $h }}</th>@endforeach
            </tr></thead><tbody>
            @foreach($report['rows'] as $row)
                <tr class="{{ $row['delayed']?'bg-danger-50/40 dark:bg-danger-950/10':'' }}">
                    <td class="octram-report-code"><a class="text-primary-600 hover:underline" href="{{ $row['url'] }}">{{ $row['documentNumber'] }}</a></td>
                    <td class="octram-report-code">{{ $row['customerOrderNumber'] }}</td><td class="octram-report-text">{{ $row['customer'] }}</td><td class="octram-report-text">{{ $row['project'] }}</td>
                    <td class="octram-report-date">{{ $row['orderDate'] }}</td><td class="octram-report-date">{{ $row['deliveryDate'] }}</td>
                    <td class="octram-report-text">{{ $row['statusLabel'] }}</td><td class="octram-report-number">{{ number_format($row['percentage'],2) }}%</td>
                    <td class="octram-report-number">{{ $row['itemsCount'] }}</td><td class="octram-report-number">{{ $row['completedItems'] }}</td><td class="octram-report-number">{{ $row['remainingItems'] }}</td>
                    <td class="octram-report-number">{{ $row['remainingQuantity'] }}</td><td class="octram-report-number">{{ $row['invoiceCount'] }}</td><td class="octram-report-number">{{ $row['attachmentCount'] }}</td>
                    <td class="octram-report-text">{{ $row['delayed']?'متأخر':'—' }}</td><td class="octram-report-text">{{ is_null($row['days'])?'—':($row['days']<0?'متأخر '.abs($row['days']).' يوم':'متبقي '.$row['days'].' يوم') }}</td>
                </tr>
            @endforeach
            </tbody></x-reports.table>
        @endif
    </x-filament::section>
    </div>
</x-filament-panels::page>
