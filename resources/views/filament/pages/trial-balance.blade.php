<x-filament-panels::page>
    <div class="octram-report space-y-6" dir="rtl">
        <form wire:submit="runReport" class="space-y-4">
            {{ $this->form }}
            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">عرض الميزان</x-filament::button>
                <x-filament::button tag="a" :href="$this->printUrl()" target="_blank" color="gray" icon="heroicon-o-printer">طباعة</x-filament::button>
            </div>
        </form>

        @php $money = fn ($v) => number_format((float) $v, 2); @endphp
        <x-filament::section>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border p-4"><div class="text-sm text-gray-500">إجمالي حركة المدين</div><div class="mt-2 text-xl font-bold" dir="ltr">{{ $money($report['totals']['period_debit']) }} ج.م</div></div>
                <div class="rounded-xl border p-4"><div class="text-sm text-gray-500">إجمالي حركة الدائن</div><div class="mt-2 text-xl font-bold" dir="ltr">{{ $money($report['totals']['period_credit']) }} ج.م</div></div>
                <div class="rounded-xl border p-4"><div class="text-sm text-gray-500">حالة الميزان</div><div class="mt-2"><x-filament::badge :color="$report['totals']['balanced'] ? 'success' : 'danger'">{{ $report['totals']['balanced'] ? 'متزن' : 'غير متزن' }}</x-filament::badge></div></div>
            </div>
        </x-filament::section>

        <x-filament::section heading="أرصدة الحسابات" icon="heroicon-o-scale">
            <x-reports.table min-width="82rem">
                <thead><tr>
                    <th rowspan="2">الكود</th><th rowspan="2">اسم الحساب</th>
                    <th colspan="2">رصيد أول المدة</th><th colspan="2">حركة الفترة</th><th colspan="2">رصيد آخر المدة</th>
                </tr><tr><th>مدين</th><th>دائن</th><th>مدين</th><th>دائن</th><th>مدين</th><th>دائن</th></tr></thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                        <tr wire:key="trial-{{ $row['id'] }}">
                            <td class="octram-report-code">{{ $row['code'] }}</td><td class="octram-report-text-wide">{{ $row['name'] }}</td>
                            @foreach(['opening_debit','opening_credit','period_debit','period_credit','closing_debit','closing_credit'] as $field)
                                <td class="octram-report-number">{{ $row[$field] > 0 ? $money($row[$field]) : '—' }}</td>
                            @endforeach
                        </tr>
                    @empty <tr><td colspan="8" class="py-10 text-center">لا توجد حركات محاسبية في الفترة المحددة.</td></tr>
                    @endforelse
                </tbody>
                <tfoot><tr class="font-bold">
                    <td colspan="2">الإجمالي</td>
                    @foreach(['opening_debit','opening_credit','period_debit','period_credit','closing_debit','closing_credit'] as $field)
                        <td class="octram-report-number">{{ $money($report['totals'][$field]) }}</td>
                    @endforeach
                </tr></tfoot>
            </x-reports.table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
