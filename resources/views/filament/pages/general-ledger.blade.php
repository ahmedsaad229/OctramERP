<x-filament-panels::page>
    <div class="octram-report space-y-6" dir="rtl">
        <form wire:submit="runReport" class="space-y-4">
            {{ $this->form }}
            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">عرض الأستاذ</x-filament::button>
                @if($report)
                    <x-filament::button tag="a" :href="$this->printUrl()" target="_blank" color="gray" icon="heroicon-o-printer">طباعة</x-filament::button>
                @endif
            </div>
        </form>

        @if($report)
            @php $money = fn ($value) => number_format((float) $value, 2); @endphp

            <x-filament::section>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-xl border p-4"><div class="text-sm text-gray-500">الحساب</div><div class="mt-2 font-bold">{{ $report['account']->displayName() }}</div></div>
                    <div class="rounded-xl border p-4"><div class="text-sm text-gray-500">رصيد أول المدة</div><div class="mt-2 text-lg font-bold" dir="ltr">{{ $money(max($report['totals']['opening_debit'], $report['totals']['opening_credit'])) }} ج.م</div></div>
                    <div class="rounded-xl border p-4"><div class="text-sm text-gray-500">حركة مدين</div><div class="mt-2 text-lg font-bold text-primary-600" dir="ltr">{{ $money($report['totals']['period_debit']) }} ج.م</div></div>
                    <div class="rounded-xl border p-4"><div class="text-sm text-gray-500">حركة دائن</div><div class="mt-2 text-lg font-bold text-danger-600" dir="ltr">{{ $money($report['totals']['period_credit']) }} ج.م</div></div>
                    <div class="rounded-xl border p-4"><div class="text-sm text-gray-500">رصيد آخر المدة</div><div class="mt-2 text-lg font-bold" dir="ltr">{{ $money($report['totals']['closing_balance']) }} ج.م <span class="text-xs">{{ $report['totals']['closing_side'] }}</span></div></div>
                </div>
            </x-filament::section>

            <x-filament::section heading="حركة الحساب" icon="heroicon-o-book-open">
                <x-reports.table min-width="78rem">
                    <thead>
                        <tr>
                            <th>#</th><th>التاريخ</th><th>نوع المستند</th><th>رقم المستند</th><th>البيان</th><th>مدين</th><th>دائن</th><th>الرصيد</th><th>النوع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $index => $row)
                            <tr wire:key="ledger-{{ $row['id'] }}">
                                <td>{{ $index + 1 }}</td>
                                <td class="octram-report-code">{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                                <td>{{ $row['source_label'] }}</td>
                                <td class="octram-report-code">{{ $row['document_number'] }}</td>
                                <td class="octram-report-text-wide">{{ $row['description'] }}</td>
                                <td class="octram-report-number">{{ $row['debit'] > 0 ? $money($row['debit']) : '—' }}</td>
                                <td class="octram-report-number">{{ $row['credit'] > 0 ? $money($row['credit']) : '—' }}</td>
                                <td class="octram-report-number font-bold">{{ $money($row['running_balance']) }}</td>
                                <td>{{ $row['running_side'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-10 text-center">لا توجد حركات لهذا الحساب في الفترة المحددة.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td colspan="5">إجمالي حركة الفترة</td>
                            <td class="octram-report-number">{{ $money($report['totals']['period_debit']) }}</td>
                            <td class="octram-report-number">{{ $money($report['totals']['period_credit']) }}</td>
                            <td class="octram-report-number">{{ $money($report['totals']['closing_balance']) }}</td>
                            <td>{{ $report['totals']['closing_side'] }}</td>
                        </tr>
                    </tfoot>
                </x-reports.table>
            </x-filament::section>
        @else
            <x-filament::section><div class="py-8 text-center text-gray-500">لا توجد حسابات قابلة للترحيل أو لم يتم اختيار حساب.</div></x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
