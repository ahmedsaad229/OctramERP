<x-filament-panels::page>
    <div class="octram-report space-y-6" dir="rtl">
        <form wire:submit="runReport" class="space-y-4">
            {{ $this->form }}
            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">عرض القائمة</x-filament::button>
                <x-filament::button tag="a" :href="$this->printUrl()" target="_blank" color="gray" icon="heroicon-o-printer">طباعة</x-filament::button>
            </div>
        </form>

        @php $money = fn ($value) => number_format((float) $value, 2); @endphp

        <x-filament::section>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border p-4"><div class="text-sm text-gray-500">الإيرادات</div><div class="mt-2 text-xl font-bold" dir="ltr">{{ $money($report['totals']['revenue']) }} ج.م</div></div>
                <div class="rounded-xl border p-4"><div class="text-sm text-gray-500">مجمل الربح</div><div class="mt-2 text-xl font-bold" dir="ltr">{{ $money($report['totals']['gross_profit']) }} ج.م</div></div>
                <div class="rounded-xl border p-4"><div class="text-sm text-gray-500">الربح التشغيلي</div><div class="mt-2 text-xl font-bold" dir="ltr">{{ $money($report['totals']['operating_profit']) }} ج.م</div></div>
                <div class="rounded-xl border p-4"><div class="text-sm text-gray-500">{{ $report['totals']['is_profit'] ? 'صافي الربح' : 'صافي الخسارة' }}</div><div class="mt-2 text-xl font-bold {{ $report['totals']['is_profit'] ? 'text-success-600' : 'text-danger-600' }}" dir="ltr">{{ $money(abs($report['totals']['net_profit'])) }} ج.م</div></div>
            </div>
        </x-filament::section>

        <x-filament::section heading="تفاصيل قائمة الدخل" icon="heroicon-o-chart-bar-square">
            <x-reports.table min-width="48rem">
                <thead><tr><th>البيان</th><th style="width: 22%">المبلغ</th></tr></thead>
                <tbody>
                    @foreach($report['sections'] as $section)
                        <tr class="font-bold bg-gray-50 dark:bg-gray-800"><td>{{ $section['label'] }}</td><td class="octram-report-number">{{ $money($section['total']) }}</td></tr>
                        @foreach($section['rows'] as $row)
                            <tr><td class="octram-report-text-wide">{{ $row['code'] }} - {{ $row['name'] }}</td><td class="octram-report-number">{{ $money($row['amount']) }}</td></tr>
                        @endforeach

                        @if($section['type'] === \App\Models\Account::TYPE_COST)
                            <tr class="font-bold"><td>مجمل الربح</td><td class="octram-report-number">{{ $money($report['totals']['gross_profit']) }}</td></tr>
                        @elseif($section['type'] === \App\Models\Account::TYPE_EXPENSE)
                            <tr class="font-bold"><td>الربح التشغيلي</td><td class="octram-report-number">{{ $money($report['totals']['operating_profit']) }}</td></tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot><tr class="font-bold"><td>{{ $report['totals']['is_profit'] ? 'صافي الربح' : 'صافي الخسارة' }}</td><td class="octram-report-number">{{ $money(abs($report['totals']['net_profit'])) }}</td></tr></tfoot>
            </x-reports.table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
