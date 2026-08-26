<x-filament-panels::page>
    <style>
        .pr-report {
            --pr-primary: #123b67;
            --pr-primary-dark: #0d2f54;
            --pr-primary-soft: #edf5fc;
            --pr-border: #d7e0ea;
            --pr-muted: #64748b;
            --pr-success: #047857;
            --pr-success-soft: #ecfdf5;
            --pr-warning: #b45309;
            --pr-warning-soft: #fff7ed;
            --pr-danger: #b91c1c;
            --pr-danger-soft: #fef2f2;
        }

        .pr-report * {
            box-sizing: border-box;
        }

        .pr-panel {
            overflow: hidden;
            border: 1px solid var(--pr-border);
            border-radius: 14px;
            background: rgb(255 255 255);
            box-shadow: 0 4px 18px rgb(15 23 42 / 6%);
        }

        .dark .pr-panel {
            border-color: rgb(55 65 81);
            background: rgb(17 24 39);
        }

        .pr-panel-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--pr-border);
            background:
                linear-gradient(
                    135deg,
                    var(--pr-primary-dark),
                    var(--pr-primary)
                );
            color: white;
        }

        .dark .pr-panel-header {
            border-bottom-color: rgb(55 65 81);
        }

        .pr-panel-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }

        .pr-panel-description {
            margin-top: 4px;
            color: rgb(219 234 254);
            font-size: 12px;
        }

        .pr-filter-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            padding: 18px 20px;
        }

        .pr-field label {
            display: block;
            margin-bottom: 7px;
            color: rgb(55 65 81);
            font-size: 12px;
            font-weight: 700;
        }

        .dark .pr-field label {
            color: rgb(209 213 219);
        }

        .pr-field select,
        .pr-field input {
            width: 100%;
            height: 40px;
            border: 1px solid rgb(209 213 219);
            border-radius: 9px;
            background: white;
            padding: 0 11px;
            color: rgb(17 24 39);
            font-size: 13px;
            outline: none;
        }

        .pr-field select:focus,
        .pr-field input:focus {
            border-color: rgb(59 130 246);
            box-shadow: 0 0 0 3px rgb(59 130 246 / 12%);
        }

        .dark .pr-field select,
        .dark .pr-field input {
            border-color: rgb(75 85 99);
            background: rgb(3 7 18);
            color: white;
        }

        .pr-reset-wrap {
            display: flex;
            align-items: flex-end;
        }

        .pr-reset-button {
            width: 100%;
            height: 40px;
            border: 1px solid rgb(209 213 219);
            border-radius: 9px;
            background: rgb(249 250 251);
            color: rgb(55 65 81);
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
        }

        .pr-reset-button:hover {
            background: rgb(243 244 246);
        }

        .dark .pr-reset-button {
            border-color: rgb(75 85 99);
            background: rgb(31 41 55);
            color: rgb(229 231 235);
        }

        .pr-summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
        }

        .pr-summary-card {
            position: relative;
            overflow: hidden;
            min-height: 105px;
            border: 1px solid var(--pr-border);
            border-radius: 13px;
            background: white;
            padding: 17px;
            box-shadow: 0 3px 12px rgb(15 23 42 / 5%);
        }

        .dark .pr-summary-card {
            border-color: rgb(55 65 81);
            background: rgb(17 24 39);
        }

        .pr-summary-card::before {
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            background: var(--card-accent);
            content: "";
        }

        .pr-summary-label {
            color: var(--pr-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .pr-summary-number {
            margin-top: 9px;
            color: var(--card-accent);
            font-size: 27px;
            font-weight: 900;
            line-height: 1;
        }

        .pr-summary-note {
            margin-top: 9px;
            color: rgb(148 163 184);
            font-size: 10px;
        }

        .pr-table-wrap {
            overflow-x: auto;
        }

        .pr-table {
            width: 100%;
            min-width: 1150px;
            border-collapse: collapse;
            font-size: 12px;
        }

        .pr-table thead th {
            padding: 12px 10px;
            border-bottom: 1px solid var(--pr-border);
            background: var(--pr-primary-soft);
            color: var(--pr-primary-dark);
            font-weight: 800;
            text-align: center;
            white-space: nowrap;
        }

        .dark .pr-table thead th {
            border-bottom-color: rgb(55 65 81);
            background: rgb(30 41 59);
            color: rgb(219 234 254);
        }

        .pr-table tbody td {
            padding: 12px 10px;
            border-bottom: 1px solid rgb(229 231 235);
            color: rgb(55 65 81);
            text-align: center;
            vertical-align: middle;
        }

        .dark .pr-table tbody td {
            border-bottom-color: rgb(55 65 81);
            color: rgb(209 213 219);
        }

        .pr-table tbody tr:hover td {
            background: rgb(248 250 252);
        }

        .dark .pr-table tbody tr:hover td {
            background: rgb(31 41 55);
        }

        .pr-code {
            color: var(--pr-primary);
            font-weight: 900;
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .dark .pr-code {
            color: rgb(147 197 253);
        }

        .pr-purpose {
            max-width: 230px;
            text-align: right !important;
            line-height: 1.5;
        }

        .pr-number {
            direction: ltr;
            unicode-bidi: isolate;
            font-weight: 800;
            white-space: nowrap;
        }

        .pr-remaining {
            color: var(--pr-warning) !important;
            font-weight: 900;
        }

        .pr-zero {
            color: var(--pr-success) !important;
        }

        .pr-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 94px;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }

        .pr-badge-danger {
            background: var(--pr-danger-soft);
            color: var(--pr-danger);
        }

        .pr-badge-warning {
            background: var(--pr-warning-soft);
            color: var(--pr-warning);
        }

        .pr-badge-success {
            background: var(--pr-success-soft);
            color: var(--pr-success);
        }

        .pr-view-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            border: 1px solid rgb(191 208 226);
            border-radius: 8px;
            background: white;
            padding: 6px 10px;
            color: var(--pr-primary);
            cursor: pointer;
            font-size: 11px;
            font-weight: 800;
        }

        .pr-view-button:hover {
            background: var(--pr-primary-soft);
        }

        .dark .pr-view-button {
            border-color: rgb(75 85 99);
            background: rgb(17 24 39);
            color: rgb(147 197 253);
        }

        .pr-details-row td {
            padding: 0 !important;
            background: rgb(248 250 252);
        }

        .dark .pr-details-row td {
            background: rgb(3 7 18);
        }

        .pr-details-box {
            padding: 17px 20px 21px;
            border-top: 3px solid var(--pr-primary);
        }

        .pr-details-heading {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 13px;
        }

        .pr-details-title {
            color: var(--pr-primary-dark);
            font-size: 14px;
            font-weight: 900;
        }

        .dark .pr-details-title {
            color: rgb(191 219 254);
        }

        .pr-details-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pr-mini-stat {
            border: 1px solid var(--pr-border);
            border-radius: 8px;
            background: white;
            padding: 6px 10px;
            color: rgb(71 85 105);
            font-size: 10px;
        }

        .dark .pr-mini-stat {
            border-color: rgb(55 65 81);
            background: rgb(17 24 39);
            color: rgb(203 213 225);
        }

        .pr-items-table {
            width: 100%;
            min-width: 1000px;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border: 1px solid var(--pr-border);
            border-radius: 9px;
            background: white;
            font-size: 11px;
        }

        .dark .pr-items-table {
            border-color: rgb(55 65 81);
            background: rgb(17 24 39);
        }

        .pr-items-table th {
            padding: 9px 8px;
            border-bottom: 1px solid var(--pr-border);
            background: var(--pr-primary);
            color: white;
            font-weight: 800;
            text-align: center;
            white-space: nowrap;
        }

        .pr-items-table td {
            padding: 9px 8px !important;
            border-bottom: 1px solid rgb(229 231 235) !important;
            background: white !important;
            text-align: center;
        }

        .dark .pr-items-table td {
            border-bottom-color: rgb(55 65 81) !important;
            background: rgb(17 24 39) !important;
        }

        .pr-items-table tbody tr:last-child td {
            border-bottom: 0 !important;
        }

        .pr-empty {
            padding: 45px 20px;
            text-align: center;
        }

        .pr-empty-title {
            color: rgb(55 65 81);
            font-size: 15px;
            font-weight: 800;
        }

        .dark .pr-empty-title {
            color: rgb(229 231 235);
        }

        .pr-empty-text {
            margin-top: 6px;
            color: var(--pr-muted);
            font-size: 12px;
        }

        @media (max-width: 1100px) {
            .pr-filter-grid,
            .pr-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .pr-filter-grid,
            .pr-summary-grid {
                grid-template-columns: 1fr;
            }

            .pr-panel-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <div class="pr-report space-y-5">

        {{-- الفلاتر --}}
        <section class="pr-panel">
            <header class="pr-panel-header">
                <div>
                    <h2 class="pr-panel-title">
                        متابعة تنفيذ طلبات الشراء
                    </h2>

                    <div class="pr-panel-description">
                        متابعة البنود المطلوب إصدار أوامر توريد لها،
                        والكميات التي تم توزيعها والمتبقية.
                    </div>
                </div>

                <div style="font-size:12px;font-weight:700">
                    عدد النتائج:
                    {{ number_format($summary['requests_count']) }}
                </div>
            </header>

            <div class="pr-filter-grid">
                <div class="pr-field">
                    <label>حالة التنفيذ</label>

                    <select wire:model.live="status">
                        <option value="">كل الحالات</option>

                        <option value="not_ordered">
                            لم يتم إصدار أمر توريد
                        </option>

                        <option value="partially_ordered">
                            تم إصدار أوامر جزئيًا
                        </option>

                        <option value="fully_ordered">
                            تم إصدار أوامر بالكامل
                        </option>
                    </select>
                </div>

                <div class="pr-field">
                    <label>المخزن</label>

                    <select wire:model.live="warehouseId">
                        <option value="">كل المخازن</option>

                        @foreach ($warehouses as $id => $name)
                            <option value="{{ $id }}">
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="pr-field">
                    <label>من تاريخ</label>

                    <input
                        type="date"
                        wire:model.live="dateFrom"
                    >
                </div>

                <div class="pr-field">
                    <label>إلى تاريخ</label>

                    <input
                        type="date"
                        wire:model.live="dateUntil"
                    >
                </div>

                <div class="pr-reset-wrap">
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="pr-reset-button"
                    >
                        مسح الفلاتر
                    </button>
                </div>
            </div>
        </section>

        {{-- ملخص التقرير --}}
        <section class="pr-summary-grid">
            <div
                class="pr-summary-card"
                style="--card-accent:#2563eb"
            >
                <div class="pr-summary-label">
                    إجمالي طلبات الشراء
                </div>

                <div class="pr-summary-number">
                    {{ number_format($summary['requests_count']) }}
                </div>

                <div class="pr-summary-note">
                    طبقًا للفلاتر المختارة
                </div>
            </div>

            <div
                class="pr-summary-card"
                style="--card-accent:#b91c1c"
            >
                <div class="pr-summary-label">
                    لم يصدر لها أمر توريد
                </div>

                <div class="pr-summary-number">
                    {{ number_format($summary['not_ordered_count']) }}
                </div>

                <div class="pr-summary-note">
                    لم يبدأ تنفيذها
                </div>
            </div>

            <div
                class="pr-summary-card"
                style="--card-accent:#b45309"
            >
                <div class="pr-summary-label">
                    منفذة جزئيًا
                </div>

                <div class="pr-summary-number">
                    {{ number_format($summary['partially_ordered_count']) }}
                </div>

                <div class="pr-summary-note">
                    ما زالت بها كميات متبقية
                </div>
            </div>

            <div
                class="pr-summary-card"
                style="--card-accent:#047857"
            >
                <div class="pr-summary-label">
                    منفذة بالكامل
                </div>

                <div class="pr-summary-number">
                    {{ number_format($summary['fully_ordered_count']) }}
                </div>

                <div class="pr-summary-note">
                    جميع بنودها مكتملة
                </div>
            </div>

            <div
                class="pr-summary-card"
                style="--card-accent:#7c3aed"
            >
                <div class="pr-summary-label">
                    البنود المتبقية
                </div>

                <div class="pr-summary-number">
                    {{ number_format($summary['remaining_items_count']) }}
                </div>

                <div class="pr-summary-note">
                    تحتاج أوامر توريد
                </div>
            </div>
        </section>

        {{-- الجدول الرئيسي --}}
        <section class="pr-panel">
            <header class="pr-panel-header">
                <div>
                    <h3 class="pr-panel-title">
                        موقف طلبات الشراء
                    </h3>

                    <div class="pr-panel-description">
                        اضغط على «تفاصيل البنود» لعرض الأصناف والموردين
                        وأوامر التوريد.
                    </div>
                </div>
            </header>

            @if ($rows->isNotEmpty())
                <div class="pr-table-wrap">
                    <table class="pr-table">
                        <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>تاريخ الطلب</th>
                            <th>تاريخ الاحتياج</th>
                            <th>المخزن</th>
                            <th>طالب الشراء</th>
                            <th>الإدارة</th>
                            <th>الغرض</th>
                            <th>عدد البنود</th>
                            <th>بنود متبقية</th>
                            <th>الحالة</th>
                            <th>التفاصيل</th>
                        </tr>
                        </thead>

                        @foreach ($rows as $row)
                            @php
                                $detailsId = 'purchase-request-details-'
                                    .md5($row['code'].'-'.$loop->index);

                                $statusClass = match ($row['status']) {
                                    \App\Models\PurchaseRequest::STATUS_FULLY_ORDERED =>
                                        'pr-badge-success',

                                    \App\Models\PurchaseRequest::STATUS_PARTIALLY_ORDERED =>
                                        'pr-badge-warning',

                                    default =>
                                        'pr-badge-danger',
                                };
                            @endphp

                            <tbody
                                x-data="{ open: false }"
                                style="border-bottom:1px solid #d7e0ea"
                            >
                            <tr>
                                <td class="pr-code">
                                    {{ $row['code'] }}
                                </td>

                                <td>{{ $row['request_date'] }}</td>

                                <td>{{ $row['required_date'] }}</td>

                                <td>{{ $row['warehouse'] }}</td>

                                <td>{{ $row['requested_by'] }}</td>

                                <td>{{ $row['department'] }}</td>

                                <td class="pr-purpose">
                                    {{ $row['purpose'] }}
                                </td>

                                <td class="pr-number">
                                    {{ number_format($row['items_count']) }}
                                </td>

                                <td
                                    class="pr-number {{ $row['remaining_items_count'] > 0 ? 'pr-remaining' : 'pr-zero' }}"
                                >
                                    {{ number_format($row['remaining_items_count']) }}
                                </td>

                                <td>
                                    <span
                                        class="pr-badge {{ $statusClass }}"
                                    >
                                        {{ $row['status_label'] }}
                                    </span>
                                </td>

                                <td>
                                    <button
                                        type="button"
                                        class="pr-view-button"
                                        x-on:click="open = ! open"
                                        x-bind:aria-expanded="open"
                                        aria-controls="{{ $detailsId }}"
                                    >
                                        <span x-text="open ? 'إخفاء التفاصيل' : 'تفاصيل البنود'"></span>
                                    </button>
                                </td>
                            </tr>

                            <tr
                                id="{{ $detailsId }}"
                                class="pr-details-row"
                                x-show="open"
                                x-collapse
                                style="display:none"
                            >
                                <td colspan="11">
                                    <div class="pr-details-box">
                                        <div class="pr-details-heading">
                                            <div class="pr-details-title">
                                                تفاصيل بنود طلب الشراء:
                                                <span class="pr-code">
                                                    {{ $row['code'] }}
                                                </span>
                                            </div>

                                            <div class="pr-details-summary">
                                                <span class="pr-mini-stat">
                                                    إجمالي البنود:
                                                    <strong>{{ $row['items_count'] }}</strong>
                                                </span>

                                                <span class="pr-mini-stat">
                                                    لم يبدأ:
                                                    <strong>{{ $row['not_ordered_items_count'] }}</strong>
                                                </span>

                                                <span class="pr-mini-stat">
                                                    جزئي:
                                                    <strong>{{ $row['partially_ordered_items_count'] }}</strong>
                                                </span>

                                                <span class="pr-mini-stat">
                                                    مكتمل:
                                                    <strong>{{ $row['fully_ordered_items_count'] }}</strong>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="pr-table-wrap">
                                            <table class="pr-items-table">
                                                <thead>
                                                <tr>
                                                    <th>كود الصنف</th>
                                                    <th>الصنف</th>
                                                    <th>الوحدة</th>
                                                    <th>المطلوب</th>
                                                    <th>صدر به أوامر</th>
                                                    <th>المتبقي</th>
                                                    <th>أوامر التوريد</th>
                                                    <th>الموردون</th>
                                                    <th>الحالة</th>
                                                </tr>
                                                </thead>

                                                <tbody>
                                                @foreach ($row['items'] as $item)
                                                    @php
                                                        $itemStatusClass = match ($item['status']) {
                                                            \App\Models\PurchaseRequest::STATUS_FULLY_ORDERED =>
                                                                'pr-badge-success',

                                                            \App\Models\PurchaseRequest::STATUS_PARTIALLY_ORDERED =>
                                                                'pr-badge-warning',

                                                            default =>
                                                                'pr-badge-danger',
                                                        };
                                                    @endphp

                                                    <tr>
                                                        <td class="pr-code">
                                                            {{ $item['item_code'] }}
                                                        </td>

                                                        <td style="text-align:right">
                                                            <strong>
                                                                {{ $item['item_name'] }}
                                                            </strong>
                                                        </td>

                                                        <td>
                                                            {{ $item['unit_name'] }}
                                                        </td>

                                                        <td class="pr-number">
                                                            {{ \App\Support\QuantityFormatter::formatForDisplay($item['requested_quantity']) }}
                                                        </td>

                                                        <td class="pr-number">
                                                            {{ \App\Support\QuantityFormatter::formatForDisplay($item['ordered_quantity']) }}
                                                        </td>

                                                        <td
                                                            class="pr-number {{ $item['remaining_quantity'] > 0 ? 'pr-remaining' : 'pr-zero' }}"
                                                        >
                                                            {{ \App\Support\QuantityFormatter::formatForDisplay($item['remaining_quantity']) }}
                                                        </td>

                                                        <td style="text-align:right">
                                                            {{ $item['purchase_orders'] }}
                                                        </td>

                                                        <td style="text-align:right">
                                                            {{ $item['suppliers'] }}
                                                        </td>

                                                        <td>
                                                            <span
                                                                class="pr-badge {{ $itemStatusClass }}"
                                                            >
                                                                {{ $item['status_label'] }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        @endforeach
                    </table>
                </div>
            @else
                <div class="pr-empty">
                    <div class="pr-empty-title">
                        لا توجد طلبات شراء مطابقة
                    </div>

                    <div class="pr-empty-text">
                        غيّر الفلاتر أو امسحها لعرض طلبات أخرى.
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
