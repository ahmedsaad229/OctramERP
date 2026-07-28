<x-filament-panels::page>
    <div class="octram-report space-y-6" dir="rtl">
    <form wire:submit="runReport" class="space-y-4">
        {{ $this->form }}

        <x-reports.actions :excel-url="$this->excelUrl()" :print-url="$this->printUrl()" />
    </form>

    @if (! $hasRun)
        <x-filament::section>
            <div class="flex flex-col items-center gap-3 py-10 text-center text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-10 w-10" />
                <p>اختر المورد وحدد الفترة لعرض كشف الحساب.</p>
            </div>
        </x-filament::section>
    @else
        @php
            $supplier = $report['supplier'];
            $money = fn (float $amount): string => app(\App\Services\SupplierStatementService::class)->money($amount);
        @endphp

        <x-filament::section>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div><div class="text-sm text-gray-500 dark:text-gray-400">المورد</div><div class="font-semibold">{{ $supplier->name }}</div></div>
                <div><div class="text-sm text-gray-500 dark:text-gray-400">كود المورد</div><div dir="ltr" class="text-right font-medium">{{ $supplier->code ?: '—' }}</div></div>
                <div><div class="text-sm text-gray-500 dark:text-gray-400">الهاتف</div><div dir="ltr" class="text-right">{{ $supplier->mobile ?: ($supplier->phone ?: '—') }}</div></div>
                <div><div class="text-sm text-gray-500 dark:text-gray-400">عدد الحركات</div><div class="font-semibold">{{ $report['transactionCount'] }}</div></div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                    <div class="text-sm text-gray-500 dark:text-gray-400">الرصيد الافتتاحي</div>
                    <div class="mt-2 font-semibold" dir="ltr">{{ $money($report['openingBalance']) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                    <div class="text-sm text-gray-500 dark:text-gray-400">إجمالي المشتريات</div>
                    <div class="mt-2 font-semibold text-danger-600" dir="ltr">{{ $money($report['totalPurchases']) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                    <div class="text-sm text-gray-500 dark:text-gray-400">إجمالي المسدد</div>
                    <div class="mt-2 font-semibold text-success-600" dir="ltr">{{ $money($report['totalPaid']) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                    <div class="flex items-center justify-between gap-2">
                        <div class="text-sm text-gray-500 dark:text-gray-400">الرصيد الختامي</div>
                        <x-filament::badge :color="$report['statusColor']">{{ $report['statusLabel'] }}</x-filament::badge>
                    </div>
                    <div class="mt-2 font-bold" dir="ltr">{{ $money($report['closingBalance']) }}</div>
                </div>
            </div>

            @filled($data['transaction_type'] ?? null)
                <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                    نوع الحركة يصفّي الصفوف والإجماليات المعروضة فقط، بينما يظل الرصيد الافتتاحي والختامي هو الرصيد الحقيقي للمورد خلال الفترة.
                </p>
            @endfilled
        </x-filament::section>

        <x-filament::section heading="حركات الحساب" icon="heroicon-o-list-bullet">
            @if ($report['rows']->isEmpty())
                <div class="flex flex-col items-center gap-3 py-10 text-center text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-inbox" class="h-10 w-10" />
                    <p>لا توجد حركات لهذا المورد خلال الفترة المحددة.</p>
                </div>
            @else
                <x-reports.table min-width="68rem">
                        <thead>
                            <tr class="text-right text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-3">التاريخ</th><th class="px-3 py-3">نوع المستند</th>
                                <th class="px-3 py-3">رقم المستند</th><th class="px-3 py-3">البيان</th>
                                <th class="px-3 py-3 text-center">مشتريات</th><th class="px-3 py-3 text-center">مسدد</th>
                                <th class="px-3 py-3 text-center">الرصيد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['rows'] as $row)
                                <tr wire:key="supplier-statement-row-{{ $row['id'] }}">
                                    <td class="octram-report-date">{{ $row['date'] }}</td>
                                    <td class="octram-report-text">{{ $row['typeLabel'] }}</td>
                                    <td class="octram-report-code font-medium">
                                        @if ($row['url'])<a href="{{ $row['url'] }}" class="text-primary-600 hover:underline dark:text-primary-400">{{ $row['reference'] }}</a>
                                        @else {{ $row['reference'] }} @endif
                                    </td>
                                    <td class="octram-report-text-wide">{{ $row['description'] }}</td>
                                    <td class="octram-report-number">{{ $row['purchases'] > 0 ? $money($row['purchases']) : '—' }}</td>
                                    <td class="octram-report-number">{{ $row['paid'] > 0 ? $money($row['paid']) : '—' }}</td>
                                    <td class="octram-report-number font-semibold">{{ $money($row['runningBalance']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                </x-reports.table>
            @endif
        </x-filament::section>
    @endif
    </div>
</x-filament-panels::page>
