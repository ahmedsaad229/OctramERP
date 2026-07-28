<x-filament-panels::page>
    <div class="octram-report space-y-6" dir="rtl">
    <form wire:submit="refreshReport" class="space-y-4">
        {{ $this->form }}
        @include('filament.pages.partials.inventory-report-actions')
    </form>

    @php
        $quantity = fn ($value): string => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
        $badge = fn (string $status): string => match ($status) {
            'out' => 'danger',
            'low' => 'warning',
            default => 'success',
        };
    @endphp

    <x-filament::section>
        @if (($report['rows'] ?? collect())->isEmpty())
            <div class="py-10 text-center text-gray-500 dark:text-gray-400">لا توجد أصناف منخفضة الرصيد.</div>
        @else
            <x-reports.table min-width="68rem">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            @foreach (['كود الصنف', 'الصنف', 'الفئة', 'المخزن', 'الرصيد الحالي', 'حد إعادة الطلب', 'الفرق', 'الحالة'] as $heading)
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
                                <td class="octram-report-number">{{ $quantity($row['quantity']) }}</td>
                                <td class="octram-report-number">{{ $quantity($row['reorder_level']) }}</td>
                                <td class="octram-report-number">{{ $quantity($row['difference']) }}</td>
                                <td class="px-3 py-3 text-center">
                                    <x-filament::badge :color="$badge($row['status'])">{{ $row['status_label'] }}</x-filament::badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
            </x-reports.table>
        @endif
    </x-filament::section>
    </div>
</x-filament-panels::page>
