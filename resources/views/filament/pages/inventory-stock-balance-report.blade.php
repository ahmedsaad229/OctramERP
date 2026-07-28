<x-filament-panels::page>
    <div class="octram-report space-y-6" dir="rtl">
    <form wire:submit="refreshReport" class="space-y-4">
        {{ $this->form }}
        @include('filament.pages.partials.inventory-report-actions')
    </form>

    @php
        $quantity = fn ($value): string => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
        $money = fn ($value): string => number_format((float) $value, 2).' ج.م';
    @endphp

    <div class="grid gap-4 sm:grid-cols-2">
        <x-filament::section compact>
            <div class="text-sm text-gray-500 dark:text-gray-400">إجمالي الكمية</div>
            <div class="mt-1 text-xl font-bold" dir="ltr">{{ $quantity($report['total_quantity'] ?? 0) }}</div>
        </x-filament::section>
        <x-filament::section compact>
            <div class="text-sm text-gray-500 dark:text-gray-400">إجمالي قيمة المخزون</div>
            <div class="mt-1 text-xl font-bold" dir="ltr">{{ $money($report['total_value'] ?? 0) }}</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        @if (($report['rows'] ?? collect())->isEmpty())
            <div class="py-10 text-center text-gray-500 dark:text-gray-400">لا توجد أرصدة.</div>
        @else
            <x-reports.table
                min-width="72rem"
                style="box-shadow: inset 0 0 0 1px var(--octram-report-border);"
            >
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            @foreach (['كود الصنف', 'اسم الصنف', 'الفئة', 'المخزن', 'الوحدة', 'الكمية الحالية', 'متوسط التكلفة', 'قيمة المخزون'] as $heading)
                                <th class="whitespace-nowrap px-3 py-3 text-center">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['rows'] as $row)
                            <tr>
                                <td class="octram-report-code">{{ $row['item_code'] }}</td>
                                <td class="octram-report-text">{{ $row['item_name'] }}</td>
                                <td class="octram-report-text">{{ $row['category'] }}</td>
                                <td class="octram-report-text">{{ $row['warehouse'] }}</td>
                                <td class="px-3 py-3 text-center">{{ $row['unit'] }}</td>
                                <td class="octram-report-number">{{ $quantity($row['quantity']) }}</td>
                                <td class="octram-report-number">{{ $money($row['average_cost']) }}</td>
                                <td class="octram-report-number font-semibold">{{ $money($row['inventory_value']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
            </x-reports.table>
        @endif
    </x-filament::section>
    </div>
</x-filament-panels::page>
