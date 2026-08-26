<x-filament-panels::page>
    <style>
        .sr-page {
            --sr-primary: #123b67;
            --sr-primary-light: #2563eb;
            --sr-border: #d7e1ed;
            --sr-muted: #64748b;
            --sr-bg: #f7f9fc;
            --sr-white: #ffffff;
            --sr-success: #059669;
            --sr-warning: #d97706;
            --sr-purple: #7c3aed;
            direction: rtl;
        }

        .sr-card {
            border: 1px solid var(--sr-border);
            border-radius: 14px;
            background: var(--sr-white);
            box-shadow: 0 5px 20px rgba(15, 23, 42, .06);
        }

        .sr-page-title {
            margin-bottom: 4px;
            color: #0f172a;
            font-size: 28px;
            font-weight: 800;
        }

        .sr-page-description {
            margin-bottom: 22px;
            color: var(--sr-muted);
            font-size: 14px;
        }

        .sr-filter-card {
            padding: 22px;
        }

        .sr-filter-heading {
            margin-bottom: 18px;
        }

        .sr-filter-heading h2 {
            margin: 0;
            color: #172033;
            font-size: 18px;
            font-weight: 800;
        }

        .sr-filter-heading p {
            margin: 5px 0 0;
            color: var(--sr-muted);
            font-size: 13px;
        }

        .sr-filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 17px;
        }

        .sr-field {
            min-width: 0;
        }

        .sr-field label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .sr-input,
        .sr-select {
            width: 100%;
            min-height: 43px;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            background: #fff;
            color: #172033;
            font-family: inherit;
            font-size: 13px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .sr-input:focus,
        .sr-select:focus {
            border-color: var(--sr-primary-light);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        }

        .sr-error {
            margin-top: 5px;
            color: #dc2626;
            font-size: 12px;
        }

        .sr-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 20px;
        }

        .sr-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
            padding: 8px 16px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: transform .1s, opacity .15s;
        }

        .sr-btn:hover {
            opacity: .91;
        }

        .sr-btn:active {
            transform: translateY(1px);
        }

        .sr-btn-primary {
            background: var(--sr-primary-light);
            color: #fff;
        }

        .sr-btn-secondary {
            border-color: #cbd5e1;
            background: #fff;
            color: #334155;
        }

        .sr-btn-success {
            background: var(--sr-success);
            color: #fff;
        }

        .sr-icon {
            width: 17px;
            height: 17px;
        }

        .sr-kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-top: 20px;
        }

        .sr-kpi {
            position: relative;
            min-height: 116px;
            padding: 18px 18px 16px;
            overflow: hidden;
        }

        .sr-kpi::after {
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            content: "";
        }

        .sr-kpi-blue::after { background: #2563eb; }
        .sr-kpi-green::after { background: #16a34a; }
        .sr-kpi-orange::after { background: #f59e0b; }
        .sr-kpi-purple::after { background: #8b5cf6; }
        .sr-kpi-navy::after { background: #123b67; }

        .sr-kpi-label {
            color: var(--sr-muted);
            font-size: 13px;
            font-weight: 700;
        }

        .sr-kpi-value {
            margin-top: 13px;
            color: #111827;
            font-size: 22px;
            font-weight: 900;
            direction: ltr;
            text-align: right;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .sr-kpi-currency {
            margin-right: 3px;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }

        .sr-table-card {
            margin-top: 20px;
            overflow: hidden;
        }

        .sr-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 17px 20px;
            border-bottom: 1px solid var(--sr-border);
        }

        .sr-table-title {
            margin: 0;
            color: #172033;
            font-size: 18px;
            font-weight: 800;
        }

        .sr-table-actions {
            display: flex;
            gap: 8px;
        }

        .sr-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .sr-table {
            width: 100%;
            min-width: 1080px;
            border-collapse: collapse;
            table-layout: auto;
        }

        .sr-table th {
            padding: 12px 11px;
            border-bottom: 1px solid var(--sr-border);
            background: #f5f8fc;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .sr-table td {
            padding: 12px 11px;
            border-bottom: 1px solid #e8edf4;
            color: #334155;
            font-size: 12.5px;
            vertical-align: middle;
        }

        .sr-table tbody tr:hover {
            background: #f8fbff;
        }

        .sr-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .sr-number {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .sr-document-number {
            color: #2563eb;
            font-weight: 800;
        }

        .sr-money {
            direction: ltr;
            unicode-bidi: isolate;
            text-align: right;
            white-space: nowrap;
        }

        .sr-total-money {
            color: #123b67;
            font-weight: 900;
        }

        .sr-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 52px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }

        .sr-badge-cash {
            background: #dcfce7;
            color: #15803d;
        }

        .sr-badge-credit {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .sr-row-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sr-action-icon {
            display: inline-grid;
            place-items: center;
            width: 32px;
            height: 32px;
            border: 1px solid #d6e0eb;
            border-radius: 7px;
            background: #fff;
            color: #475569;
            text-decoration: none;
        }

        .sr-action-icon:hover {
            border-color: #93b4d9;
            background: #f1f7fd;
            color: #123b67;
        }

        .sr-empty {
            padding: 45px 15px !important;
            color: var(--sr-muted) !important;
            text-align: center !important;
        }

        .sr-footer-summary {
            padding: 12px 20px;
            border-top: 1px solid var(--sr-border);
            background: #fafcff;
            color: var(--sr-muted);
            font-size: 12px;
        }

        @media (max-width: 1100px) {
            .sr-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 850px) {
            .sr-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .sr-filter-grid,
            .sr-kpi-grid {
                grid-template-columns: 1fr;
            }

            .sr-page-title {
                font-size: 23px;
            }

            .sr-table-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        .dark .sr-card {
            border-color: #334155;
            background: #111827;
        }

        .dark .sr-page-title,
        .dark .sr-filter-heading h2,
        .dark .sr-table-title,
        .dark .sr-kpi-value {
            color: #f8fafc;
        }

        .dark .sr-filter-heading p,
        .dark .sr-kpi-label {
            color: #94a3b8;
        }

        .dark .sr-input,
        .dark .sr-select {
            border-color: #475569;
            background: #0f172a;
            color: #f8fafc;
        }

        .dark .sr-table th {
            border-color: #334155;
            background: #0f172a;
            color: #cbd5e1;
        }

        .dark .sr-table td {
            border-color: #273449;
            color: #e2e8f0;
        }

        .dark .sr-table tbody tr:hover {
            background: #172033;
        }
    </style>

    <div class="sr-page">
        <h1 class="sr-page-title">تقارير المبيعات</h1>
        <div class="sr-page-description">
            عرض وتحليل إجماليات وتفاصيل فواتير البيع.
        </div>

        <section class="sr-card sr-filter-card">
            <div class="sr-filter-heading">
                <h2>فلاتر تقرير المبيعات</h2>
                <p>حدد الفترة أو العميل أو المخزن ثم اضغط على عرض التقرير.</p>
            </div>

            <div class="sr-filter-grid">
                <div class="sr-field">
                    <label>من تاريخ</label>
                    <input
                        class="sr-input"
                        type="date"
                        wire:model="date_from"
                    >
                    @error('date_from')
                        <div class="sr-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="sr-field">
                    <label>إلى تاريخ</label>
                    <input
                        class="sr-input"
                        type="date"
                        wire:model="date_until"
                    >
                    @error('date_until')
                        <div class="sr-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="sr-field">
                    <label>رقم الفاتورة</label>
                    <input
                        class="sr-input"
                        type="text"
                        wire:model="document_number"
                        placeholder="اكتب رقم الفاتورة"
                    >
                </div>

                <div class="sr-field">
                    <label>العميل</label>
                    <select class="sr-select" wire:model="customer_id">
                        <option value="">كل العملاء</option>
                        @foreach ($this->customerOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sr-field">
                    <label>المخزن</label>
                    <select class="sr-select" wire:model="warehouse_id">
                        <option value="">كل المخازن</option>
                        @foreach ($this->warehouseOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sr-field">
                    <label>نوع التعامل</label>
                    <select class="sr-select" wire:model="payment_type">
                        <option value="">الكل</option>
                        @foreach ($this->paymentTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="sr-actions">
                <button
                    type="button"
                    class="sr-btn sr-btn-primary"
                    wire:click="showReport"
                >
                    <x-filament::icon
                        icon="heroicon-o-magnifying-glass"
                        class="sr-icon"
                    />
                    عرض التقرير
                </button>

                <button
                    type="button"
                    class="sr-btn sr-btn-secondary"
                    wire:click="resetFilters"
                >
                    <x-filament::icon
                        icon="heroicon-o-arrow-path"
                        class="sr-icon"
                    />
                    إعادة تعيين
                </button>
            </div>
        </section>

        <section class="sr-kpi-grid">
            <div class="sr-card sr-kpi sr-kpi-blue">
                <div class="sr-kpi-label">عدد الفواتير</div>
                <div class="sr-kpi-value">
                    {{ number_format($this->totals['invoices_count']) }}
                </div>
            </div>

            <div class="sr-card sr-kpi sr-kpi-green">
                <div class="sr-kpi-label">إجمالي قبل الضريبة</div>
                <div class="sr-kpi-value">
                    {{ number_format($this->totals['subtotal'], 2) }}
                    <span class="sr-kpi-currency">ج.م</span>
                </div>
            </div>

            <div class="sr-card sr-kpi sr-kpi-orange">
                <div class="sr-kpi-label">إجمالي الخصم</div>
                <div class="sr-kpi-value">
                    {{ number_format($this->totals['discount'], 2) }}
                    <span class="sr-kpi-currency">ج.م</span>
                </div>
            </div>

            <div class="sr-card sr-kpi sr-kpi-purple">
                <div class="sr-kpi-label">إجمالي الضريبة</div>
                <div class="sr-kpi-value">
                    {{ number_format($this->totals['tax'], 2) }}
                    <span class="sr-kpi-currency">ج.م</span>
                </div>
            </div>

            <div class="sr-card sr-kpi sr-kpi-navy">
                <div class="sr-kpi-label">صافي المبيعات</div>
                <div class="sr-kpi-value">
                    {{ number_format($this->totals['total'], 2) }}
                    <span class="sr-kpi-currency">ج.م</span>
                </div>
            </div>
        </section>

        <section class="sr-card sr-table-card">
            <div class="sr-table-header">
                <h2 class="sr-table-title">تفاصيل فواتير البيع</h2>

                <div class="sr-table-actions">
                    <a
                        class="sr-btn sr-btn-secondary"
                        href="{{ $this->printUrl() }}"
                        target="_blank"
                    >
                        <x-filament::icon
                            icon="heroicon-o-printer"
                            class="sr-icon"
                        />
                        طباعة
                    </a>

                    <a
                        class="sr-btn sr-btn-success"
                        href="{{ $this->excelUrl() }}"
                    >
                        <x-filament::icon
                            icon="heroicon-o-table-cells"
                            class="sr-icon"
                        />
                        Excel
                    </a>
                </div>
            </div>

            <div class="sr-table-wrap">
                <table class="sr-table">
                    <thead>
                    <tr>
                        <th>م</th>
                        <th>التاريخ</th>
                        <th>رقم الفاتورة</th>
                        <th>العميل</th>
                        <th>المخزن</th>
                        <th>نوع التعامل</th>
                        <th>قبل الضريبة</th>
                        <th>الخصم</th>
                        <th>الضريبة</th>
                        <th>الإجمالي</th>
                        <th>إجراء</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse ($this->records as $invoice)
                        @php
                            $paymentType = $invoice->payment_type;

                            $paymentLabel = is_object($paymentType)
                                && method_exists($paymentType, 'label')
                                    ? $paymentType->label()
                                    : (string) $paymentType;

                            $isCash = str_contains(
                                mb_strtolower($paymentLabel),
                                'نقد'
                            ) || (
                                is_object($paymentType)
                                && isset($paymentType->value)
                                && $paymentType->value === 'cash'
                            );
                        @endphp

                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td class="sr-number">
                                {{ $invoice->invoice_date?->format('d/m/Y') }}
                            </td>

                            <td>
                                <span class="sr-document-number sr-number">
                                    {{ $invoice->document_number }}
                                </span>
                            </td>

                            <td>{{ $invoice->customer?->name ?: '—' }}</td>

                            <td>{{ $invoice->warehouse?->name ?: '—' }}</td>

                            <td>
                                <span class="sr-badge {{ $isCash ? 'sr-badge-cash' : 'sr-badge-credit' }}">
                                    {{ $paymentLabel }}
                                </span>
                            </td>

                            <td class="sr-money">
                                {{ number_format((float) $invoice->items_subtotal, 2) }}
                            </td>

                            <td class="sr-money">
                                {{ number_format((float) $invoice->discount_amount, 2) }}
                            </td>

                            <td class="sr-money">
                                {{ number_format((float) $invoice->tax_amount, 2) }}
                            </td>

                            <td class="sr-money sr-total-money">
                                {{ number_format($invoice->totalAmount(), 2) }}
                            </td>

                            <td>
                                <div class="sr-row-actions">
                                    <a
                                        class="sr-action-icon"
                                        title="فتح الفاتورة"
                                        href="{{ \App\Filament\Resources\SalesInvoices\SalesInvoiceResource::getUrl('view', ['record' => $invoice]) }}"
                                    >
                                        <x-filament::icon
                                            icon="heroicon-o-eye"
                                            class="sr-icon"
                                        />
                                    </a>

                                    <a
                                        class="sr-action-icon"
                                        title="طباعة الفاتورة"
                                        target="_blank"
                                        href="{{ route('sales-invoices.print', $invoice) }}"
                                    >
                                        <x-filament::icon
                                            icon="heroicon-o-printer"
                                            class="sr-icon"
                                        />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="sr-empty">
                                لا توجد فواتير مطابقة للفلاتر المحددة.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="sr-footer-summary">
                عدد الفواتير الظاهرة:
                <strong>{{ number_format($this->records->count()) }}</strong>
            </div>
        </section>
    </div>
</x-filament-panels::page>
