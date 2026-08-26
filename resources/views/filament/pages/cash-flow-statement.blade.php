<x-filament-panels::page>
    <div class="octram-report space-y-6" dir="rtl">
        <form wire:submit="runReport" class="space-y-4">
            {{ $this->form }}

            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                    عرض التدفقات النقدية
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    :href="$this->printUrl()"
                    target="_blank"
                    color="gray"
                    icon="heroicon-o-printer"
                >
                    طباعة
                </x-filament::button>
            </div>
        </form>

        @php
            $money = static fn ($value): string => number_format((float) $value, 2);
            $display = static fn ($value): string => abs((float) $value) < 0.005
                ? '—'
                : number_format(abs((float) $value), 2);
        @endphp

        <x-filament::section>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">رصيد أول الفترة</div>
                    <div class="mt-2 text-xl font-bold" dir="ltr">{{ $money($report['totals']['opening_balance']) }} ج.م</div>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">إجمالي الداخل</div>
                    <div class="mt-2 text-xl font-bold text-success-600" dir="ltr">{{ $money($report['totals']['inflows']) }} ج.م</div>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">إجمالي الخارج</div>
                    <div class="mt-2 text-xl font-bold text-danger-600" dir="ltr">{{ $money($report['totals']['outflows']) }} ج.م</div>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">صافي التغير</div>
                    <div class="mt-2 text-xl font-bold" dir="ltr">{{ $money($report['totals']['net_change']) }} ج.م</div>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">رصيد آخر الفترة</div>
                    <div class="mt-2 text-xl font-bold" dir="ltr">{{ $money($report['totals']['closing_balance']) }} ج.م</div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <x-filament::badge :color="$report['totals']['balanced'] ? 'success' : 'danger'">
                    {{ $report['totals']['balanced'] ? 'متطابقة مع دفتر الأستاذ' : 'يوجد فرق في المطابقة' }}
                </x-filament::badge>

                @unless ($report['totals']['balanced'])
                    <span class="text-sm text-danger-600">
                        الفرق: {{ $money(abs($report['totals']['difference'])) }} ج.م
                    </span>
                @endunless

                <span class="text-sm text-gray-500">
                    عدد الحركات: {{ $report['totals']['movement_count'] }}
                </span>
            </div>
        </x-filament::section>

        @foreach ($report['sections'] as $section)
            <x-filament::section :heading="$section['label']" icon="heroicon-o-banknotes">
                <div class="mb-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border p-3">
                        <div class="text-xs text-gray-500">الداخل</div>
                        <div class="mt-1 font-bold text-success-600" dir="ltr">{{ $money($section['inflows']) }} ج.م</div>
                    </div>
                    <div class="rounded-lg border p-3">
                        <div class="text-xs text-gray-500">الخارج</div>
                        <div class="mt-1 font-bold text-danger-600" dir="ltr">{{ $money($section['outflows']) }} ج.م</div>
                    </div>
                    <div class="rounded-lg border p-3">
                        <div class="text-xs text-gray-500">الصافي</div>
                        <div class="mt-1 font-bold" dir="ltr">{{ $money($section['net']) }} ج.م</div>
                    </div>
                </div>

                <x-reports.table min-width="72rem">
                    <thead>
                        <tr>
                            <th>البند</th>
                            <th>المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($section['categories'] as $category)
                            <tr class="font-bold">
                                <td class="octram-report-text-wide">{{ $category['label'] }}</td>
                                <td class="octram-report-number">{{ $display($category['amount']) }}</td>
                            </tr>

                            @if ($report['details'])
                                @foreach ($category['rows'] as $row)
                                    <tr wire:key="cash-flow-{{ $row['id'] }}">
                                        <td class="octram-report-text-wide pe-8">
                                            {{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}
                                            — {{ $row['document_number'] ?: 'بدون رقم' }}
                                            — {{ $row['description'] }}
                                        </td>
                                        <td class="octram-report-number">
                                            {{ $row['amount'] >= 0 ? $display($row['amount']) : '('.$display($row['amount']).')' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @empty
                            <tr>
                                <td colspan="2" class="py-8 text-center">لا توجد تدفقات ضمن هذا النشاط في الفترة المحددة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td>صافي {{ $section['label'] }}</td>
                            <td class="octram-report-number">{{ $money($section['net']) }}</td>
                        </tr>
                    </tfoot>
                </x-reports.table>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
