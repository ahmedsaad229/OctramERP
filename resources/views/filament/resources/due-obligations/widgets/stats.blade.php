@php
    $data = $this->getDashboardData();
    $money = static fn (float|int $value): string => number_format((float) $value, 2);
    $netIsPositive = $data['position']['net'] >= 0;
@endphp

<x-filament-widgets::widget>
    <div
        class="due-dashboard"
        x-data="{ overdueModal: null }"
        @keydown.escape.window="overdueModal = null"
    >
        <div class="due-dashboard-grid">
            <section class="due-card due-card-customers">
                <div class="due-card-heading">
                    <div>
                        <div class="due-card-label">إجمالي فواتير العملاء</div>
                        <div class="due-card-total">{{ $money($data['customers']['total']) }} <small>ج.م</small></div>
                    </div>
                    <x-filament::icon icon="heroicon-o-users" />
                </div>

                <div class="due-card-breakdown">
                    <div>
                        <span>تم التحصيل</span>
                        <strong class="due-positive">{{ $money($data['customers']['paid']) }} <small>ج.م</small></strong>
                    </div>
                    <div>
                        <span>المتبقي</span>
                        <strong class="due-negative">{{ $money($data['customers']['remaining']) }} <small>ج.م</small></strong>
                    </div>
                </div>
            </section>

            <section class="due-card due-card-suppliers">
                <div class="due-card-heading">
                    <div>
                        <div class="due-card-label">إجمالي فواتير المشتريات</div>
                        <div class="due-card-total">{{ $money($data['suppliers']['total']) }} <small>ج.م</small></div>
                    </div>
                    <x-filament::icon icon="heroicon-o-shopping-cart" />
                </div>

                <div class="due-card-breakdown">
                    <div>
                        <span>تم السداد</span>
                        <strong class="due-info">{{ $money($data['suppliers']['paid']) }} <small>ج.م</small></strong>
                    </div>
                    <div>
                        <span>المتبقي</span>
                        <strong class="due-negative">{{ $money($data['suppliers']['remaining']) }} <small>ج.م</small></strong>
                    </div>
                </div>
            </section>

            <section class="due-card due-card-overdue">
                <div class="due-card-heading due-card-heading-center">
                    <div>
                        <div class="due-card-label">المتأخرات</div>
                        <div class="due-card-total">{{ $money($data['overdue']['amount']) }} <small>ج.م</small></div>
                    </div>
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" />
                </div>

                <div class="due-overdue-split">
                    <div>
                        <span>عملاء</span>
                        <button
                            type="button"
                            class="due-count-link"
                            @click="overdueModal = 'customers'"
                            @disabled($data['overdue']['customers_count'] === 0)
                        >
                            {{ $data['overdue']['customers_count'] }} فاتورة
                        </button>
                        <strong>{{ $money($data['overdue']['customers_amount']) }} <small>ج.م</small></strong>
                    </div>

                    <div>
                        <span>موردون</span>
                        <button
                            type="button"
                            class="due-count-link"
                            @click="overdueModal = 'suppliers'"
                            @disabled($data['overdue']['suppliers_count'] === 0)
                        >
                            {{ $data['overdue']['suppliers_count'] }} فاتورة
                        </button>
                        <strong>{{ $money($data['overdue']['suppliers_amount']) }} <small>ج.م</small></strong>
                    </div>
                </div>

                <div class="due-overdue-total">
                    <span>الإجمالي</span>
                    <strong>{{ $money($data['overdue']['amount']) }} <small>ج.م</small></strong>
                </div>
            </section>
        </div>

        <section class="due-position-bar">
            <div class="due-rate">
                <div
                    class="due-rate-ring"
                    style="--rate: {{ min(100, max(0, $data['position']['collection_rate'])) * 3.6 }}deg"
                >
                    <span>{{ number_format($data['position']['collection_rate'], 2) }}%</span>
                </div>
                <div>
                    <strong>نسبة التحصيل</strong>
                    <span>من إجمالي فواتير العملاء</span>
                </div>
            </div>

            <div>
                <span>لنا عند العملاء</span>
                <strong class="due-positive">{{ $money($data['position']['customer_remaining']) }} <small>ج.م</small></strong>
            </div>

            <div>
                <span>علينا للموردين</span>
                <strong class="due-negative">{{ $money($data['position']['supplier_remaining']) }} <small>ج.م</small></strong>
            </div>

            <div>
                <span>صافي المركز</span>
                <strong class="{{ $netIsPositive ? 'due-positive' : 'due-negative' }}">
                    {{ $netIsPositive ? '+' : '' }}{{ $money($data['position']['net']) }} <small>ج.م</small>
                </strong>
            </div>
        </section>

        <div
            x-cloak
            x-show="overdueModal !== null"
            x-transition.opacity
            class="due-modal-backdrop"
            @click.self="overdueModal = null"
        >
            <section class="due-modal" role="dialog" aria-modal="true">
                <header>
                    <h3 x-show="overdueModal === 'customers'">
                        فواتير العملاء المتأخرة ({{ $data['overdue']['customers_count'] }})
                    </h3>
                    <h3 x-show="overdueModal === 'suppliers'">
                        فواتير الموردين المتأخرة ({{ $data['overdue']['suppliers_count'] }})
                    </h3>
                    <button type="button" @click="overdueModal = null" aria-label="إغلاق">×</button>
                </header>

                <div class="due-modal-table-wrap" x-show="overdueModal === 'customers'">
                    @include('filament.resources.due-obligations.widgets.partials.overdue-table', [
                        'records' => $data['overdue']['customer_records'],
                        'total' => $data['overdue']['customers_amount'],
                        'money' => $money,
                    ])
                </div>

                <div class="due-modal-table-wrap" x-show="overdueModal === 'suppliers'">
                    @include('filament.resources.due-obligations.widgets.partials.overdue-table', [
                        'records' => $data['overdue']['supplier_records'],
                        'total' => $data['overdue']['suppliers_amount'],
                        'money' => $money,
                    ])
                </div>
            </section>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .due-dashboard { display: grid; gap: 1rem; }
        .due-dashboard-grid { display: grid; grid-template-columns: 1fr 1fr 1.45fr; gap: 1rem; }
        .due-card, .due-position-bar { border: 1px solid rgb(226 232 240); background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgb(15 23 42 / .06); }
        .due-card { min-height: 15rem; padding: 1.2rem; }
        .due-card-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid rgb(226 232 240); }
        .due-card-heading > svg { width: 1.45rem; height: 1.45rem; padding: .65rem; box-sizing: content-box; border-radius: 9999px; }
        .due-card-customers .due-card-heading > svg { color: rgb(22 163 74); background: rgb(240 253 244); }
        .due-card-suppliers .due-card-heading > svg { color: rgb(37 99 235); background: rgb(239 246 255); }
        .due-card-overdue .due-card-heading > svg { color: rgb(220 38 38); background: rgb(254 242 242); }
        .due-card-label { font-size: .95rem; font-weight: 700; color: rgb(51 65 85); }
        .due-card-customers .due-card-label, .due-card-customers .due-card-total { color: rgb(21 128 61); }
        .due-card-suppliers .due-card-label, .due-card-suppliers .due-card-total { color: rgb(29 78 216); }
        .due-card-overdue .due-card-label, .due-card-overdue .due-card-total { color: rgb(220 38 38); }
        .due-card-total { margin-top: .45rem; font-size: 1.75rem; font-weight: 800; direction: ltr; unicode-bidi: isolate; }
        small { font-size: .65em; font-weight: 600; }
        .due-card-breakdown { display: grid; gap: 1rem; padding-top: 1.1rem; }
        .due-card-breakdown > div { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .due-card-breakdown span, .due-overdue-split span, .due-overdue-total span, .due-position-bar > div > span { color: rgb(100 116 139); font-size: .85rem; }
        .due-card-breakdown strong, .due-overdue-split strong, .due-overdue-total strong, .due-position-bar strong { font-size: 1.05rem; direction: ltr; unicode-bidi: isolate; }
        .due-positive { color: rgb(21 128 61); }
        .due-negative { color: rgb(220 38 38); }
        .due-info { color: rgb(29 78 216); }
        .due-card-heading-center { justify-content: center; text-align: center; position: relative; }
        .due-card-heading-center > svg { position: absolute; inset-inline-end: 0; }
        .due-overdue-split { display: grid; grid-template-columns: 1fr 1fr; padding: 1rem 0; border-bottom: 1px solid rgb(226 232 240); }
        .due-overdue-split > div { display: grid; justify-items: center; gap: .35rem; padding: 0 1rem; }
        .due-overdue-split > div + div { border-inline-start: 1px solid rgb(226 232 240); }
        .due-count-link { color: rgb(37 99 235); font-weight: 700; text-decoration: underline; text-underline-offset: .2rem; cursor: pointer; }
        .due-count-link:disabled { color: rgb(148 163 184); cursor: default; text-decoration: none; }
        .due-overdue-total { display: flex; align-items: center; justify-content: center; gap: .75rem; padding-top: .9rem; }
        .due-overdue-total strong { color: rgb(220 38 38); }
        .due-position-bar { display: grid; grid-template-columns: 1.25fr repeat(3, 1fr); align-items: center; min-height: 6rem; padding: 1rem 1.25rem; }
        .due-position-bar > div { display: grid; justify-items: center; gap: .4rem; padding: .2rem 1rem; }
        .due-position-bar > div + div { border-inline-start: 1px solid rgb(226 232 240); }
        .due-rate { grid-template-columns: auto 1fr !important; justify-items: start !important; }
        .due-rate > div:last-child { display: grid; gap: .3rem; }
        .due-rate > div:last-child span { color: rgb(100 116 139); font-size: .8rem; }
        .due-rate-ring { width: 4rem; height: 4rem; display: grid; place-items: center; border-radius: 50%; background: conic-gradient(rgb(22 163 74) var(--rate), rgb(220 252 231) 0); position: relative; }
        .due-rate-ring::after { content: ''; position: absolute; inset: .42rem; border-radius: 50%; background: white; }
        .due-rate-ring span { z-index: 1; color: rgb(21 128 61); font-weight: 800; font-size: .8rem; }
        .due-modal-backdrop { position: fixed; inset: 0; z-index: 60; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .48); }
        .due-modal { width: min(60rem, 96vw); max-height: 88vh; overflow: hidden; border-radius: 1rem; background: white; box-shadow: 0 24px 60px rgb(15 23 42 / .28); }
        .due-modal header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.2rem; border-bottom: 1px solid rgb(226 232 240); }
        .due-modal h3 { font-size: 1.05rem; font-weight: 800; }
        .due-modal header button { width: 2rem; height: 2rem; border-radius: .5rem; color: rgb(100 116 139); font-size: 1.5rem; }
        .due-modal-table-wrap { overflow: auto; max-height: calc(88vh - 4.5rem); padding: 1rem; }
        .due-modal-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .due-modal-table th, .due-modal-table td { padding: .7rem .6rem; border: 1px solid rgb(226 232 240); text-align: center; }
        .due-modal-table th { background: rgb(248 250 252); color: rgb(51 65 85); }
        .due-modal-table tfoot td { font-weight: 800; color: rgb(220 38 38); background: rgb(254 242 242); }
        .due-modal-table a { color: rgb(37 99 235); font-weight: 700; text-decoration: underline; }
        @media (max-width: 1100px) { .due-dashboard-grid { grid-template-columns: 1fr 1fr; } .due-card-overdue { grid-column: 1 / -1; } .due-position-bar { grid-template-columns: 1fr 1fr; } .due-position-bar > div:nth-child(3) { border-inline-start: 0; } }
        @media (max-width: 700px) { .due-dashboard-grid, .due-position-bar { grid-template-columns: 1fr; } .due-card-overdue { grid-column: auto; } .due-position-bar > div + div, .due-position-bar > div:nth-child(3) { border-inline-start: 0; border-top: 1px solid rgb(226 232 240); padding-top: 1rem; } .due-rate { justify-content: center; } .due-card { min-height: auto; } }
        @media (prefers-color-scheme: dark) { .due-card, .due-position-bar, .due-modal { background: rgb(17 24 39); border-color: rgb(55 65 81); } .due-card-heading, .due-overdue-split, .due-position-bar > div + div, .due-modal header, .due-modal-table th, .due-modal-table td { border-color: rgb(55 65 81); } .due-card-label, .due-card-breakdown span, .due-overdue-split span, .due-overdue-total span, .due-position-bar > div > span { color: rgb(203 213 225); } .due-rate-ring::after { background: rgb(17 24 39); } .due-modal-table th { background: rgb(31 41 55); } }
    </style>
</x-filament-widgets::widget>
