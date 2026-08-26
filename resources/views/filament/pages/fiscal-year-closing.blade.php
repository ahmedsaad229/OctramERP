<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="createYear">
            {{ $this->yearForm }}
            <div class="mt-4">
                <x-filament::button type="submit" icon="heroicon-o-plus">
                    إنشاء السنة المالية
                </x-filament::button>
            </div>
        </form>

        <form wire:submit="previewClosing">
            {{ $this->closingForm }}
            <div class="mt-4 flex flex-wrap gap-3">
                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                    معاينة الإقفال
                </x-filament::button>

                @php($selectedYear = $this->selectedYear())

                @if ($selectedYear?->isClosed() && auth()->user()?->is_admin)
                    <x-filament::button
                        type="button"
                        color="danger"
                        wire:click="reopenYear"
                        wire:confirm="سيتم حذف قيد الإقفال وفتح السنة مرة أخرى. هل أنت متأكد؟"
                    >
                        إلغاء الإقفال
                    </x-filament::button>
                @endif
            </div>
        </form>

        @if ($preview)
            <x-filament::section heading="نتيجة المعاينة">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="rounded-xl border p-4">
                        <div class="text-sm text-gray-500">السنة</div>
                        <div class="font-bold">{{ $preview['year']->name }}</div>
                    </div>
                    <div class="rounded-xl border p-4">
                        <div class="text-sm text-gray-500">صافي النتيجة</div>
                        <div class="font-bold">
                            {{ number_format(abs($preview['net_result']), 2) }} ج.م
                            — {{ $preview['net_result'] >= 0 ? 'ربح' : 'خسارة' }}
                        </div>
                    </div>
                    <div class="rounded-xl border p-4">
                        <div class="text-sm text-gray-500">إجمالي المدين</div>
                        <div class="font-bold">{{ number_format($preview['total_debit'], 2) }} ج.م</div>
                    </div>
                    <div class="rounded-xl border p-4">
                        <div class="text-sm text-gray-500">إجمالي الدائن</div>
                        <div class="font-bold">{{ number_format($preview['total_credit'], 2) }} ج.م</div>
                    </div>
                </div>

                @if ($preview['unbalanced_entries']->isNotEmpty())
                    <div class="mt-4 rounded-xl border border-danger-300 bg-danger-50 p-4 text-danger-700">
                        لا يمكن الإقفال. يوجد {{ $preview['unbalanced_entries']->count() }} قيد غير متزن داخل الفترة.
                    </div>
                @endif

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="border-b">
                            <th class="p-2 text-right">كود الحساب</th>
                            <th class="p-2 text-right">الحساب</th>
                            <th class="p-2 text-right">مدين الإقفال</th>
                            <th class="p-2 text-right">دائن الإقفال</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($preview['lines'] as $line)
                            <tr class="border-b">
                                <td class="p-2">{{ $line['code'] }}</td>
                                <td class="p-2">{{ $line['name'] }}</td>
                                <td class="p-2">{{ number_format($line['debit'], 2) }}</td>
                                <td class="p-2">{{ number_format($line['credit'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-4 text-center">لا توجد أرصدة إيرادات أو مصروفات تحتاج إلى إقفال.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <x-filament::button
                        tag="a"
                        color="gray"
                        icon="heroicon-o-printer"
                        target="_blank"
                        :href="route('fiscal-year-closing.print', [
                            'fiscal_year_id' => $preview['year']->getKey(),
                            'retained_earnings_account_id' => $preview['retained_account']->getKey(),
                        ])"
                    >
                        طباعة / حفظ PDF
                    </x-filament::button>

                    @if ($preview['can_close'])
                        <x-filament::button
                            type="button"
                            color="danger"
                            wire:click="closeYear"
                            wire:confirm="سيتم إنشاء قيد إقفال آلي ثم قفل الفترة ومنع تعديل أو حذف مستنداتها. هل أنت متأكد؟"
                        >
                            تنفيذ إقفال السنة
                        </x-filament::button>
                    @endif
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
