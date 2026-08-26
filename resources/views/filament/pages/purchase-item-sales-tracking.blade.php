<x-filament-panels::page>

<style>
    .pist-wrap {
        direction: rtl;
        width: 100%;
    }

    .pist-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-top: 14px;
        margin-bottom: 18px;
    }

    .pist-summary {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .pist-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 16px;
        min-height: 82px;
        box-shadow: 0 1px 2px rgba(0,0,0,.03);
    }

    .dark .pist-card {
        background: #111827;
        border-color: #374151;
    }

    .pist-card-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 7px;
    }

    .pist-card-value {
        color: #111827;
        font-size: 21px;
        line-height: 1;
        font-weight: 800;
        direction: ltr;
        text-align: right;
    }

    .dark .pist-card-value {
        color: #f9fafb;
    }

    .pist-card.sold .pist-card-value {
        color: #15803d;
    }

    .pist-card.remaining .pist-card-value {
        color: #dc2626;
    }

    .pist-table-card {
        width: 100%;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }

    .dark .pist-table-card {
        background: #111827;
        border-color: #374151;
    }

    .pist-table-scroll {
        width: 100%;
        overflow-x: auto;
    }

    .pist-table {
        width: 100%;
        min-width: 1450px;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 13px;
    }

    .pist-table thead th {
        background: #f8fafc;
        color: #334155;
        font-size: 12px;
        font-weight: 800;
        padding: 12px 10px;
        border-bottom: 1px solid #dbe3ec;
        text-align: right;
        vertical-align: middle;
        white-space: nowrap;
    }

    .dark .pist-table thead th {
        background: #1f2937;
        color: #e5e7eb;
        border-color: #374151;
    }

    .pist-table tbody td {
        padding: 11px 10px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1f2937;
        line-height: 1.55;
    }

    .dark .pist-table tbody td {
        color: #e5e7eb;
        border-color: #374151;
    }

    .pist-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .pist-table tbody tr:hover {
        background: #fafcff;
    }

    .dark .pist-table tbody tr:hover {
        background: #172033;
    }

    .pist-center {
        text-align: center !important;
    }

    .pist-ltr {
        direction: ltr;
        unicode-bidi: isolate;
        text-align: center;
        white-space: nowrap;
    }

    .pist-document {
        direction: ltr;
        unicode-bidi: isolate;
        font-weight: 800;
        text-align: center;
        white-space: nowrap;
    }

    .pist-item-code {
        direction: ltr;
        unicode-bidi: isolate;
        text-align: center;
        font-weight: 700;
    }

    .pist-item-name {
        font-weight: 700;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .pist-supplier {
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .pist-badge {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        min-width: 92px;
        border-radius: 999px;
        padding: 5px 9px;
        font-size: 11px;
        line-height: 1.3;
        font-weight: 800;
        white-space: nowrap;
    }

    .pist-badge-green {
        color: #166534;
        background: #dcfce7;
    }

    .pist-badge-yellow {
        color: #854d0e;
        background: #fef9c3;
    }

    .pist-badge-red {
        color: #991b1b;
        background: #fee2e2;
    }

    .pist-customers {
        font-size: 12px;
        line-height: 1.65;
    }

    .pist-customer {
        margin-bottom: 3px;
    }

    .pist-details summary {
        list-style: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
        border-radius: 7px;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .pist-details summary::-webkit-details-marker {
        display: none;
    }

    .pist-detail-box {
        margin-top: 8px;
        width: 560px;
        max-width: 75vw;
        border: 1px solid #dbe3ec;
        border-radius: 9px;
        background: #fff;
        padding: 8px;
        box-shadow: 0 8px 25px rgba(15,23,42,.10);
    }

    .dark .pist-detail-box {
        background: #111827;
        border-color: #374151;
    }

    .pist-detail-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }

    .pist-detail-table th {
        padding: 7px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 800;
        text-align: right;
    }

    .pist-detail-table td {
        padding: 7px !important;
        border-bottom: 1px solid #eef2f7 !important;
        font-size: 11px;
    }

    .pist-empty {
        text-align: center;
        padding: 35px !important;
        color: #64748b !important;
    }

    @media (max-width: 1280px) {
        .pist-summary {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .pist-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<div class="pist-wrap">

    <form wire:submit="runReport">

        {{ $this->form }}

        <div class="pist-actions">

            <x-filament::button
                type="submit"
                icon="heroicon-o-magnifying-glass"
            >
                عرض التقرير
            </x-filament::button>

            <x-filament::button
                type="button"
                color="gray"
                wire:click="resetFilters"
                icon="heroicon-o-arrow-path"
            >
                إعادة تعيين
            </x-filament::button>

            <x-filament::button
                tag="a"
                color="danger"
                icon="heroicon-o-printer"
                :href="$this->printUrl()"
                target="_blank"
            >
                طباعة / PDF
            </x-filament::button>

            <x-filament::button
                tag="a"
                color="success"
                icon="heroicon-o-document-arrow-down"
                :href="$this->excelUrl()"
                target="_blank"
            >
                Excel
            </x-filament::button>

        </div>

    </form>

    @if ($report)

        @php
            $summary = $report['summary'] ?? [];
            $rows = $report['rows'] ?? [];
        @endphp

        <div class="pist-summary">

            <div class="pist-card">
                <div class="pist-card-label">
                    إجمالي كمية الشراء
                </div>

                <div class="pist-card-value">
                    {{ number_format($summary['purchase_quantity'] ?? 0, 2) }}
                </div>
            </div>

            <div class="pist-card sold">
                <div class="pist-card-label">
                    الكمية المباعة
                </div>

                <div class="pist-card-value">
                    {{ number_format($summary['sold_quantity'] ?? 0, 2) }}
                </div>
            </div>

            <div class="pist-card remaining">
                <div class="pist-card-label">
                    الكمية غير المباعة
                </div>

                <div class="pist-card-value">
                    {{ number_format($summary['remaining_quantity'] ?? 0, 2) }}
                </div>
            </div>

            <div class="pist-card">
                <div class="pist-card-label">
                    تم البيع بالكامل
                </div>

                <div class="pist-card-value">
                    {{ $summary['fully_sold'] ?? 0 }}
                </div>
            </div>

            <div class="pist-card">
                <div class="pist-card-label">
                    بيع جزئي
                </div>

                <div class="pist-card-value">
                    {{ $summary['partially_sold'] ?? 0 }}
                </div>
            </div>

            <div class="pist-card">
                <div class="pist-card-label">
                    لم يتم البيع
                </div>

                <div class="pist-card-value">
                    {{ $summary['not_sold'] ?? 0 }}
                </div>
            </div>

        </div>

        <div class="pist-table-card">

            <div class="pist-table-scroll">

                <table class="pist-table">

                    <colgroup>
                        <col style="width:115px">
                        <col style="width:95px">
                        <col style="width:170px">
                        <col style="width:100px">
                        <col style="width:330px">
                        <col style="width:90px">
                        <col style="width:80px">
                        <col style="width:80px">
                        <col style="width:120px">
                        <col style="width:190px">
                        <col style="width:105px">
                    </colgroup>

                    <thead>
                    <tr>
                        <th class="pist-center">فاتورة الشراء</th>
                        <th class="pist-center">تاريخ الشراء</th>
                        <th>المورد</th>
                        <th class="pist-center">كود الصنف</th>
                        <th>الصنف</th>
                        <th class="pist-center">كمية الشراء</th>
                        <th class="pist-center">المباع</th>
                        <th class="pist-center">المتبقي</th>
                        <th class="pist-center">الحالة</th>
                        <th>العملاء</th>
                        <th class="pist-center">تفاصيل البيع</th>
                    </tr>
                    </thead>

                    <tbody>

                    @forelse ($rows as $row)

                        <tr>

                            <td class="pist-document">
                                {{ $row['purchase_document'] }}
                            </td>

                            <td class="pist-center">
                                {{ $row['purchase_date'] }}
                            </td>

                            <td class="pist-supplier">
                                {{ $row['supplier'] }}
                            </td>

                            <td class="pist-item-code">
                                {{ $row['item_code'] }}
                            </td>

                            <td class="pist-item-name">
                                {{ $row['item_name'] }}
                            </td>

                            <td class="pist-ltr">
                                {{ number_format($row['purchase_quantity'], 2) }}
                            </td>

                            <td class="pist-ltr">
                                {{ number_format($row['sold_quantity'], 2) }}
                            </td>

                            <td class="pist-ltr">
                                {{ number_format($row['remaining_quantity'], 2) }}
                            </td>

                            <td class="pist-center">

                                @if ($row['status'] === 'fully_sold')

                                    <span class="pist-badge pist-badge-green">
                                        تم البيع بالكامل
                                    </span>

                                @elseif ($row['status'] === 'partially_sold')

                                    <span class="pist-badge pist-badge-yellow">
                                        بيع جزئي
                                    </span>

                                @else

                                    <span class="pist-badge pist-badge-red">
                                        لم يتم البيع
                                    </span>

                                @endif

                            </td>

                            <td class="pist-customers">

                                @if (count($row['customers']))

                                    @foreach ($row['customers'] as $customer)

                                        <div class="pist-customer">
                                            {{ $customer }}
                                        </div>

                                    @endforeach

                                @else
                                    —
                                @endif

                            </td>

                            <td class="pist-center">

                                @if (count($row['allocations']))

                                    <details class="pist-details">

                                        <summary>
                                            عرض التفاصيل
                                        </summary>

                                        <div class="pist-detail-box">

                                            <table class="pist-detail-table">

                                                <thead>
                                                <tr>
                                                    <th>فاتورة البيع</th>
                                                    <th>التاريخ</th>
                                                    <th>العميل</th>
                                                    <th class="pist-center">الكمية</th>
                                                    <th class="pist-center">سعر البيع</th>
                                                </tr>
                                                </thead>

                                                <tbody>

                                                @foreach ($row['allocations'] as $sale)

                                                    <tr>

                                                        <td class="pist-document">
                                                            {{ $sale['document_number'] ?: '—' }}
                                                        </td>

                                                        <td>
                                                            {{ $sale['invoice_date'] ?: '—' }}
                                                        </td>

                                                        <td>
                                                            {{ $sale['customer'] }}
                                                        </td>

                                                        <td class="pist-ltr">
                                                            {{ number_format($sale['quantity'], 2) }}
                                                        </td>

                                                        <td class="pist-ltr">
                                                            {{ number_format($sale['unit_price'], 2) }}
                                                        </td>

                                                    </tr>

                                                @endforeach

                                                </tbody>

                                            </table>

                                        </div>

                                    </details>

                                @else
                                    —
                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="11" class="pist-empty">
                                لا توجد بيانات مطابقة.
                            </td>
                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    @endif

</div>

</x-filament-panels::page>
