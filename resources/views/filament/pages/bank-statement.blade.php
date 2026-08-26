<x-filament-panels::page>
    <form wire:submit="runReport" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">عرض الكشف</x-filament::button>
    </form>

    @if($report)
        <div class="grid gap-4 md:grid-cols-4 mt-6">
            @foreach([
                ['الرصيد الافتتاحي',$report['opening_balance']],
                ['إجمالي الإيداعات',$report['total_debit']],
                ['إجمالي المسحوبات',$report['total_credit']],
                ['الرصيد الختامي',$report['closing_balance']],
            ] as [$label,$value])
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="text-sm text-gray-500">{{ $label }}</div>
                    <div class="mt-2 text-xl font-bold">{{ number_format((float)$value,2) }} {{ $report['account']->currency }}</div>
                </div>
            @endforeach
        </div>
        <div class="mt-6 overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="p-3">التاريخ</th><th class="p-3">رقم المستند</th><th class="p-3">المرجع</th><th class="p-3">البيان</th><th class="p-3">إيداع</th><th class="p-3">سحب</th><th class="p-3">الرصيد</th></tr></thead>
                <tbody>
                @forelse($report['rows'] as $row)<tr class="border-t dark:border-gray-700"><td class="p-3">{{ $row['date'] }}</td><td class="p-3">{{ $row['document_number'] ?: '—' }}</td><td class="p-3">{{ $row['reference_number'] ?: '—' }}</td><td class="p-3">{{ $row['description'] }}</td><td class="p-3">{{ $row['debit'] > 0 ? number_format($row['debit'],2) : '—' }}</td><td class="p-3">{{ $row['credit'] > 0 ? number_format($row['credit'],2) : '—' }}</td><td class="p-3 font-bold">{{ number_format($row['balance'],2) }}</td></tr>@empty<tr><td colspan="7" class="p-8 text-center text-gray-500">لا توجد حركات خلال الفترة.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
