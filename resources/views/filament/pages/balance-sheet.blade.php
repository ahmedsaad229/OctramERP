<x-filament-panels::page>
    <div class="octram-report space-y-6" dir="rtl">
        <form wire:submit="runReport" class="space-y-4">
            {{ $this->form }}
            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">عرض الميزانية</x-filament::button>
                <x-filament::button tag="a" :href="$this->printUrl()" target="_blank" color="gray" icon="heroicon-o-printer">طباعة</x-filament::button>
            </div>
        </form>

        @php $money = fn ($value) => number_format((float) $value, 2); @endphp

        <x-filament::section>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">إجمالي الأصول</div>
                    <div class="mt-2 text-xl font-bold" dir="ltr">{{ $money($report['totals']['assets']) }} ج.م</div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">إجمالي الالتزامات</div>
                    <div class="mt-2 text-xl font-bold" dir="ltr">{{ $money($report['totals']['liabilities']) }} ج.م</div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">إجمالي حقوق الملكية</div>
                    <div class="mt-2 text-xl font-bold" dir="ltr">{{ $money($report['totals']['equity']) }} ج.م</div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">حالة الميزانية</div>
                    <div class="mt-2 text-xl font-bold {{ $report['totals']['balanced'] ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $report['totals']['balanced'] ? 'متزنة' : 'غير متزنة' }}
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="تفاصيل الميزانية العمومية" icon="heroicon-o-scale">
            <x-reports.table min-width="48rem">
                <thead><tr><th>البيان</th><th style="width: 24%">المبلغ</th></tr></thead>
                <tbody>
                    @foreach($report['sections'] as $section)
                        <tr class="font-bold bg-gray-50 dark:bg-gray-800">
                            <td>{{ $section['label'] }}</td>
                            <td class="octram-report-number">{{ $money($section['total']) }}</td>
                        </tr>
                        @foreach($section['rows'] as $row)
                            <tr>
                                <td class="octram-report-text-wide">{{ $row['code'] }} - {{ $row['name'] }}</td>
                                <td class="octram-report-number">{{ $money($row['amount']) }}</td>
                            </tr>
                        @endforeach

                        @if($section['type'] === \App\Models\Account::TYPE_EQUITY)
                            <tr>
                                <td class="octram-report-text-wide">{{ $report['totals']['is_profit'] ? 'نتيجة النشاط المتراكمة (ربح)' : 'نتيجة النشاط المتراكمة (خسارة)' }}</td>
                                <td class="octram-report-number">{{ $money($report['totals']['current_result']) }}</td>
                            </tr>
                            <tr class="font-bold">
                                <td>إجمالي حقوق الملكية بعد نتيجة النشاط</td>
                                <td class="octram-report-number">{{ $money($report['totals']['equity']) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold">
                        <td>إجمالي الالتزامات وحقوق الملكية</td>
                        <td class="octram-report-number">{{ $money($report['totals']['liabilities_and_equity']) }}</td>
                    </tr>
                    <tr class="font-bold {{ $report['totals']['balanced'] ? 'text-success-600' : 'text-danger-600' }}">
                        <td>{{ $report['totals']['balanced'] ? 'الميزانية متزنة' : 'فرق الميزانية' }}</td>
                        <td class="octram-report-number">{{ $money(abs($report['totals']['difference'])) }}</td>
                    </tr>
                </tfoot>
            </x-reports.table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
