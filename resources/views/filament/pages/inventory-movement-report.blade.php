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

    <x-filament::section>
        @if (($report['rows'] ?? collect())->isEmpty())
            <div class="py-10 text-center text-gray-500 dark:text-gray-400">لا توجد حركات.</div>
        @else
            <x-reports.table
                min-width="82rem"
                style="box-shadow: inset 0 0 0 1px var(--octram-report-border);"
            >
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            @foreach (['التاريخ', 'نوع الحركة', 'رقم المستند', 'الصنف', 'المخزن', 'وارد', 'صادر', 'الرصيد بعد الحركة', 'التكلفة'] as $heading)
                                <th class="whitespace-nowrap px-3 py-3 text-center">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['rows'] as $row)
                            <tr>
                                <td class="octram-report-date">{{ $row['date'] }}</td>
                                <td class="octram-report-text">{{ $row['type_label'] }}</td>
                                <td class="octram-report-code">{{ $row['reference'] }}</td>
                                <td class="octram-report-text">{{ $row['item'] }} <span class="text-xs text-gray-500" dir="ltr">{{ $row['item_code'] }}</span></td>
                                <td class="octram-report-text">{{ $row['warehouse'] }}</td>
                                <td class="octram-report-number text-success-600">{{ $row['inbound'] > 0 ? $quantity($row['inbound']) : '—' }}</td>
                                <td class="octram-report-number text-danger-600">{{ $row['outbound'] > 0 ? $quantity($row['outbound']) : '—' }}</td>
                                <td class="octram-report-number font-semibold">{{ $quantity($row['running_balance']) }}</td>
                                <td class="octram-report-number">{{ $money($row['unit_cost']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
            </x-reports.table>
        @endif
    </x-filament::section>
    </div>
</x-filament-panels::page>
