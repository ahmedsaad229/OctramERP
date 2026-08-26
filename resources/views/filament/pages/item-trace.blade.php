<x-filament-panels::page>
    <div class="space-y-6" dir="rtl">
        <form wire:submit="runSearch" class="space-y-4">
            {{ $this->form }}
    @if ($hasRun && filled($data['item_id'] ?? null))
        <div class="mt-4 flex justify-end">
            <a
                href="{{ route('item-trace.print', [
                    'item_id' => $data['item_id'],
                    'document_type' => $data['document_type'] ?? \App\Services\ItemTraceService::ALL,
                    'from_date' => $data['from_date'] ?? null,
                    'to_date' => $data['to_date'] ?? null,
                ]) }}"
                target="_blank"
                rel="noopener"
                class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
            >
                <span>🖨️</span>
                <span>طباعة التقرير</span>
            </a>
        </div>
    @endif

            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                    بحث
                </x-filament::button>

                <x-filament::button type="button" color="gray" wire:click="resetSearch" icon="heroicon-o-arrow-path">
                    مسح البحث
                </x-filament::button>
            </div>
        </form>

        @php
            $quantity = fn ($value): string => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
            $money = fn ($value): string => $value === null ? '—' : number_format((float) $value, 2).' ج.م';
        @endphp

        @if ($hasRun)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <x-filament::section compact>
                    <div class="text-sm text-gray-500">عدد النتائج</div>
                    <div class="mt-1 text-2xl font-bold">{{ number_format((int) ($report['count'] ?? 0)) }}</div>
                </x-filament::section>

                <x-filament::section compact>
                    <div class="text-sm text-gray-500">إجمالي الكمية في النتائج</div>
                    <div class="mt-1 text-2xl font-bold">{{ $quantity($report['total_quantity'] ?? 0) }}</div>
                </x-filament::section>

                <x-filament::section compact>
                    <div class="text-sm text-gray-500">مكان البحث</div>
                    <div class="mt-1 text-lg font-bold">
                        {{ app(\App\Services\ItemTraceService::class)->documentTypeOptions()[$data['document_type'] ?? 'all'] ?? 'الكل' }}
                    </div>
                </x-filament::section>
            </div>

            <x-filament::section>
                @if (($report['rows'] ?? collect())->isEmpty())
                    <div class="py-12 text-center text-gray-500 dark:text-gray-400">
                        لم يتم العثور على الصنف في المستندات المحددة.
                    </div>
                @else
                    
                    <style>
                        .octram-trace-wrap {
                            overflow-x: auto;
                            border: 1px solid #d7e1ec;
                            border-radius: 12px;
                            background: #fff;
                            box-shadow: 0 4px 16px rgba(15, 23, 42, .06);
                        }

                        .octram-trace-table {
                            width: 100%;
                            min-width: 1250px;
                            border-collapse: separate;
                            border-spacing: 0;
                            table-layout: fixed;
                            font-size: 13px;
                            direction: rtl;
                        }

                        .octram-trace-table thead th {
                            background: #123f70;
                            color: #fff;
                            padding: 13px 8px;
                            font-weight: 800;
                            text-align: center;
                            border-left: 1px solid rgba(255,255,255,.18);
                            white-space: nowrap;
                        }

                        .octram-trace-table thead th:first-child {
                            border-top-right-radius: 10px;
                        }

                        .octram-trace-table thead th:last-child {
                            border-top-left-radius: 10px;
                            border-left: 0;
                        }

                        .octram-trace-table tbody td {
                            padding: 12px 9px;
                            border-left: 1px solid #dde6ef;
                            border-bottom: 1px solid #dde6ef;
                            vertical-align: middle;
                            color: #172033;
                            background: #fff;
                        }

                        .octram-trace-table tbody tr:nth-child(even) td {
                            background: #f8fbfe;
                        }

                        .octram-trace-table tbody tr:hover td {
                            background: #eef6ff;
                        }

                        .octram-trace-table tbody td:last-child {
                            border-left: 0;
                        }

                        .trace-center {
                            text-align: center;
                        }

                        .trace-party {
                            text-align: right;
                            font-weight: 600;
                            line-height: 1.55;
                            overflow-wrap: anywhere;
                        }

                        .trace-number,
                        .trace-date,
                        .trace-money,
                        .trace-qty {
                            direction: ltr;
                            unicode-bidi: isolate;
                            text-align: center;
                            white-space: nowrap;
                        }

                        .trace-number {
                            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                            font-weight: 800;
                            color: #0f4c8a;
                        }

                        .trace-date {
                            font-weight: 700;
                        }

                        .trace-money,
                        .trace-qty {
                            font-weight: 800;
                        }

                        .trace-type {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            padding: 5px 10px;
                            border-radius: 7px;
                            background: #e8f2ff;
                            color: #125394;
                            border: 1px solid #cfe3fa;
                            font-weight: 800;
                            white-space: nowrap;
                        }

                        .trace-status {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-width: 88px;
                            padding: 5px 9px;
                            border-radius: 999px;
                            border: 1px solid;
                            font-size: 12px;
                            font-weight: 800;
                            white-space: nowrap;
                        }

                        .trace-open {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-width: 58px;
                            padding: 6px 10px;
                            border-radius: 7px;
                            background: #1559a5;
                            color: #fff !important;
                            font-size: 12px;
                            font-weight: 800;
                            text-decoration: none;
                            box-shadow: 0 2px 5px rgba(21,89,165,.18);
                        }

                        .trace-open:hover {
                            background: #0e477f;
                        }

                        .trace-doc-link {
                            color: #1559a5;
                            text-decoration: none;
                            font-weight: 800;
                        }

                        .trace-doc-link:hover {
                            text-decoration: underline;
                        }
                    </style>

                    <div class="octram-trace-wrap">
                        <table class="octram-trace-table">

                            <colgroup>
                                <col style="width:9%">
                                <col style="width:12%">
                                <col style="width:13%">
                                <col style="width:22%">
                                <col style="width:7%">
                                <col style="width:11%">
                                <col style="width:12%">
                                <col style="width:9%">
                                <col style="width:5%">
                            </colgroup>

                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>نوع المستند</th>
                                    <th>رقم المستند</th>
                                    <th>الطرف / المخزن</th>
                                    <th>الكمية</th>
                                    <th>سعر الوحدة</th>
                                    <th>الإجمالي</th>
                                    <th>الحالة</th>
                                    <th>فتح</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($report['rows'] as $row)

                                    @php
                                        $status = trim((string) ($row['status'] ?? '—'));

                                        $statusStyle = match (true) {
                                            str_contains($status, 'جزئي')
                                                => 'background:#fff7dd;color:#9a6700;border-color:#f7d774;',

                                            str_contains($status, 'تم'),
                                            str_contains($status, 'مرحلة'),
                                            str_contains($status, 'بالكامل'),
                                            str_contains($status, 'منفذ')
                                                => 'background:#e8f8ee;color:#16733b;border-color:#b9e6c9;',

                                            str_contains($status, 'غير'),
                                            str_contains($status, 'لم')
                                                => 'background:#f1f5f9;color:#5f6b7a;border-color:#d8e0e8;',

                                            default
                                                => 'background:#eef5ff;color:#245b96;border-color:#d2e2f5;',
                                        };
                                    @endphp

                                    <tr>

                                        <td class="trace-date">
                                            {{ $row['date'] }}
                                        </td>

                                        <td class="trace-center">
                                            <span class="trace-type">
                                                {{ $row['type_label'] }}
                                            </span>
                                        </td>

                                        <td class="trace-number">

                                            @if ($row['url'])

                                                <a
                                                    href="{{ $row['url'] }}"
                                                    class="trace-doc-link"
                                                >
                                                    {{ $row['number'] }}
                                                </a>

                                            @else

                                                {{ $row['number'] }}

                                            @endif

                                        </td>

                                        <td class="trace-party">
                                            {{ $row['party'] }}
                                        </td>

                                        <td class="trace-qty">
                                            {{ $quantity($row['quantity']) }}
                                        </td>

                                        <td class="trace-money">
                                            {{ $money($row['unit_price']) }}
                                        </td>

                                        <td class="trace-money">
                                            {{ $money($row['line_total']) }}
                                        </td>

                                        <td class="trace-center">
                                            <span
                                                class="trace-status"
                                                style="{{ $statusStyle }}"
                                            >
                                                {{ $status }}
                                            </span>
                                        </td>

                                        <td class="trace-center">

                                            @if ($row['url'])

                                                <a
                                                    href="{{ $row['url'] }}"
                                                    class="trace-open"
                                                >
                                                    فتح
                                                </a>

                                            @else
                                                —
                                            @endif

                                        </td>

                                    </tr>

                                @endforeach
                            </tbody>

                        </table>
                    </div>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
