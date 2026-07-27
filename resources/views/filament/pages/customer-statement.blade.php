<x-filament-panels::page>
    <form wire:submit="runReport" class="space-y-4">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                عرض الكشف
            </x-filament::button>

            <x-filament::button type="button" color="gray" icon="heroicon-o-arrow-path" wire:click="resetReport">
                إعادة تعيين
            </x-filament::button>

            @if ($printUrl = $this->printUrl())
                <x-filament::button tag="a" :href="$printUrl" icon="heroicon-o-printer">
                    طباعة كشف الحساب
                </x-filament::button>
            @endif
        </div>
    </form>

    @if (! $hasRun)
        <x-filament::section>
            <div class="flex flex-col items-center gap-3 py-10 text-center text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-10 w-10" />
                <p>اختر العميل وحدد الفترة لعرض كشف الحساب.</p>
            </div>
        </x-filament::section>
    @else
        @php
            $customer = $report['customer'];
            $money = fn (float $amount): string => app(\App\Services\CustomerStatementService::class)->money($amount);
        @endphp

        <x-filament::section>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">العميل</div>
                    <div class="font-semibold">{{ $customer->name }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">كود العميل</div>
                    <div dir="ltr" class="text-right font-medium">{{ $customer->code ?: '—' }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">الهاتف</div>
                    <div dir="ltr" class="text-right">{{ $customer->mobile ?: ($customer->phone ?: '—') }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">عدد الحركات</div>
                    <div class="font-semibold">{{ $report['transactionCount'] }}</div>
                </div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                    <div class="text-sm text-gray-500 dark:text-gray-400">الرصيد الافتتاحي</div>
                    <div class="mt-2 font-semibold" dir="ltr">{{ $money($report['openingBalance']) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                    <div class="text-sm text-gray-500 dark:text-gray-400">إجمالي المديونية</div>
                    <div class="mt-2 font-semibold text-danger-600" dir="ltr">{{ $money($report['totalDebt']) }}</div>
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
                    نوع الحركة يصفّي الصفوف والإجماليات المعروضة فقط، بينما يظل الرصيد الافتتاحي والختامي هو الرصيد الحقيقي للعميل خلال الفترة.
                </p>
            @endfilled
        </x-filament::section>

        <x-filament::section heading="حركات الحساب" icon="heroicon-o-list-bullet">
            @if ($report['rows']->isEmpty())
                <div class="flex flex-col items-center gap-3 py-10 text-center text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-inbox" class="h-10 w-10" />
                    <p>لا توجد حركات لهذا العميل خلال الفترة المحددة.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] table-auto text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-right text-gray-500 dark:border-white/10 dark:text-gray-400">
                                <th class="px-3 py-3">التاريخ</th>
                                <th class="px-3 py-3">نوع المستند</th>
                                <th class="px-3 py-3">رقم المستند</th>
                                <th class="px-3 py-3">البيان</th>
                                <th class="px-3 py-3 text-center">مديونية</th>
                                <th class="px-3 py-3 text-center">مسدد</th>
                                <th class="px-3 py-3 text-center">الرصيد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['rows'] as $row)
                                <tr wire:key="customer-statement-row-{{ $row['id'] }}" class="border-b border-gray-100 dark:border-white/5">
                                    <td class="whitespace-nowrap px-3 py-3" dir="ltr">{{ $row['date'] }}</td>
                                    <td class="px-3 py-3">{{ $row['typeLabel'] }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 font-medium" dir="ltr">
                                        @if ($row['url'])
                                            <a href="{{ $row['url'] }}" class="text-primary-600 hover:underline dark:text-primary-400">{{ $row['reference'] }}</a>
                                        @else
                                            {{ $row['reference'] }}
                                        @endif
                                    </td>
                                    <td class="max-w-sm px-3 py-3">{{ $row['description'] }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-center" dir="ltr">{{ $row['debit'] > 0 ? $money($row['debit']) : '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-center" dir="ltr">{{ $row['credit'] > 0 ? $money($row['credit']) : '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-center font-semibold" dir="ltr">{{ $money($row['runningBalance']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
