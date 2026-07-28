<x-filament-panels::page>
    <div class="octram-report space-y-6" dir="rtl">
    <form wire:submit="runReport" class="space-y-4">
        {{ $this->form }}
        <x-reports.actions :excel-url="$this->excelUrl()" :print-url="$this->printUrl()" />
    </form>

    @if (! $hasRun)
        <x-filament::section><div class="py-10 text-center text-gray-500 dark:text-gray-400">اختر الصنف وحدد الفترة لعرض حركة الصنف.</div></x-filament::section>
    @else
        @php
            $quantity = fn (float $value): string => rtrim(rtrim(number_format($value, 2), '0'), '.');
            $money = fn (float $value): string => number_format($value, 2).' ج.م';
        @endphp
        <x-filament::section>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div><div class="text-sm text-gray-500">الصنف</div><div class="font-semibold">{{ $report['item']->name }}</div></div>
                <div><div class="text-sm text-gray-500">كود الصنف</div><div dir="ltr">{{ $report['item']->code }}</div></div>
                <div><div class="text-sm text-gray-500">الوحدة</div><div>{{ $report['item']->unit?->name ?: '—' }}</div></div>
                <div><div class="text-sm text-gray-500">المخزن</div><div>{{ $report['warehouse']?->name ?: 'كل المخازن' }}</div></div>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    'الرصيد الافتتاحي' => $quantity($report['openingQuantity']),
                    'إجمالي الوارد' => $quantity($report['totalInbound']),
                    'إجمالي المنصرف' => $quantity($report['totalOutbound']),
                    'الرصيد الختامي' => $quantity($report['closingQuantity']),
                    'قيمة الرصيد الافتتاحي' => $money($report['openingValue']),
                    'قيمة الرصيد الختامي' => $money($report['closingValue']),
                    'متوسط التكلفة الختامي' => $money($report['closingAverage']),
                    'عدد الحركات' => $report['transactionCount'],
                ] as $label => $value)
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10"><div class="text-sm text-gray-500">{{ $label }}</div><div class="mt-2 font-semibold" dir="ltr">{{ $value }}</div></div>
                @endforeach
            </div>
            @filled($data['transaction_type'] ?? null)
                <p class="mt-4 text-xs text-gray-500">نوع الحركة يصفّي الصفوف وإجماليات الفترة المعروضة فقط، بينما يظل الرصيد الافتتاحي والختامي مبنيًا على جميع الحركات الفعلية.</p>
            @endfilled
        </x-filament::section>

        <x-filament::section heading="حركات الصنف" icon="heroicon-o-list-bullet">
            @if ($report['rows']->isEmpty())
                <div class="py-10 text-center text-gray-500">لا توجد حركات لهذا الصنف خلال الفترة المحددة.</div>
            @else
                <x-reports.table min-width="96rem">
                        <thead><tr class="text-right text-gray-500">
                            @foreach (['التاريخ','نوع المستند','رقم المستند','المخزن','البيان','وارد','منصرف','الرصيد','تكلفة الوحدة','قيمة الحركة','قيمة الرصيد','متوسط التكلفة'] as $heading)<th class="whitespace-nowrap px-3 py-3 text-center">{{ $heading }}</th>@endforeach
                        </tr></thead>
                        <tbody>
                        @foreach ($report['rows'] as $row)
                            <tr>
                                <td class="octram-report-date">{{ $row['date'] }}</td>
                                <td class="octram-report-text">{{ $row['typeLabel'] }}</td>
                                <td class="octram-report-code">@if($row['url'])<a class="text-primary-600 hover:underline" href="{{ $row['url'] }}">{{ $row['reference'] }}</a>@else{{ $row['reference'] }}@endif</td>
                                <td class="octram-report-text">{{ $row['warehouse'] }}</td><td class="octram-report-text-wide">{{ $row['description'] }}</td>
                                <td class="octram-report-number">{{ $row['inbound'] > 0 ? $quantity($row['inbound']) : '—' }}</td>
                                <td class="octram-report-number">{{ $row['outbound'] > 0 ? $quantity($row['outbound']) : '—' }}</td>
                                <td class="octram-report-number">{{ $quantity($row['runningQuantity']) }}</td>
                                <td class="octram-report-number">{{ $money($row['unitCost']) }}</td>
                                <td class="octram-report-number">{{ $money($row['movementValue']) }}</td>
                                <td class="octram-report-number">{{ $money($row['runningValue']) }}</td>
                                <td class="octram-report-number">{{ $money($row['runningAverage']) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                </x-reports.table>
            @endif
        </x-filament::section>
    @endif
    </div>
</x-filament-panels::page>
