<x-filament-panels::page>
    @php
        $money = static fn ($value): string => number_format((float) $value, 2);
        $chartMax = max(
            1,
            collect($dashboard['chart'])->max(
                fn ($row) => max(
                    (float) $row['sales'],
                    (float) $row['purchases'],
                    abs((float) ($row['profit'] ?? 0))
                )
            )
        );
    @endphp

    <style>
        .octram-dashboard { direction: rtl; }
        .od-topbar {
            display:flex; justify-content:space-between; align-items:flex-end; gap:16px;
            margin-bottom:18px; flex-wrap:wrap;
        }
        .od-title h2 { margin:0; font-size:1.45rem; font-weight:800; color:#0f172a; }
        .od-title p { margin:.3rem 0 0; color:#64748b; font-size:.88rem; }
        .od-filter { min-width:230px; }
        .od-filter label { display:block; margin-bottom:6px; color:#475569; font-size:.78rem; font-weight:700; }
        .od-select {
            width:100%; border:1px solid #d7e0eb; border-radius:10px; padding:9px 12px;
            background:#fff; color:#0f172a; outline:none;
        }
        .od-period { margin-top:5px; color:#94a3b8; font-size:.72rem; }
        .od-custom-dates {
            display:grid; grid-template-columns:1fr 1fr; gap:7px; margin-top:8px;
        }
        .od-custom-dates span {
            display:block; margin-bottom:3px; font-size:.66rem; color:#64748b;
        }
        .od-date-input {
            width:100%; border:1px solid #d7e0eb; border-radius:8px;
            padding:7px 8px; background:#fff; color:#0f172a; font-size:.72rem;
        }

        .od-cards {
            display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:12px; margin-bottom:18px;
        }
        .od-card, .od-alert, .od-panel {
            border:1px solid #e2e8f0; border-radius:14px; background:#fff;
            box-shadow:0 4px 16px rgba(15,23,42,.04);
        }
        .od-card {
            padding:16px 14px; min-height:145px; text-decoration:none!important; display:block;
            transition:.16s ease;
        }
        .od-card[href]:hover, .od-alert[href]:hover { transform:translateY(-2px); box-shadow:0 8px 22px rgba(15,23,42,.08); }
        .od-card-head { display:flex; justify-content:space-between; gap:8px; align-items:center; }
        .od-card-label { font-size:.82rem; font-weight:800; color:#334155; }
        .od-card-icon {
            width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center;
            font-size:1.05rem;
        }
        .od-value { margin-top:18px; font-size:1.3rem; font-weight:900; direction:ltr; unicode-bidi:isolate; white-space:nowrap; }
        .od-currency { font-size:.7rem; margin-inline-start:3px; font-weight:700; }
        .od-card-foot { margin-top:10px; font-size:.7rem; color:#94a3b8; }
        .od-card-sub {
            margin-top:10px; padding-top:9px; border-top:1px solid #eef2f7;
            display:grid; gap:5px;
        }
        .od-card-sub-row {
            display:flex; justify-content:space-between; gap:8px; align-items:center;
            font-size:.69rem; color:#64748b;
        }
        .od-card-sub-value {
            direction:ltr; unicode-bidi:isolate; font-weight:900; white-space:nowrap;
        }
        .od-card-sub-positive { color:#059669; }
        .od-card-sub-negative { color:#dc2626; }
        .od-card-caption { margin-top:9px; font-size:.67rem; color:#64748b; }
        .tone-green .od-card-icon { background:#ecfdf5; } .tone-green .od-value { color:#15803d; }
        .tone-blue .od-card-icon { background:#eff6ff; } .tone-blue .od-value { color:#2563eb; }
        .tone-emerald .od-card-icon { background:#ecfdf5; } .tone-emerald .od-value { color:#059669; }
        .tone-orange .od-card-icon { background:#fff7ed; } .tone-orange .od-value { color:#ea580c; }
        .tone-violet .od-card-icon { background:#f5f3ff; } .tone-violet .od-value { color:#7c3aed; }
        .tone-red .od-card-icon { background:#fef2f2; } .tone-red .od-value { color:#dc2626; }

        .od-section-title {
            display:flex; align-items:center; gap:7px; margin:4px 0 10px;
            font-weight:900; color:#1e293b;
        }
        .od-alerts {
            display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; margin-bottom:18px;
        }
        .od-alert { padding:14px; min-height:125px; text-decoration:none!important; transition:.16s ease; }
        .od-alert-top { display:flex; justify-content:space-between; gap:8px; }
        .od-alert-label { font-size:.78rem; font-weight:800; color:#475569; }
        .od-alert-icon { font-size:1.15rem; }
        .od-alert-count { margin-top:12px; font-size:1.32rem; font-weight:900; color:#0f172a; }
        .od-alert-unit { font-size:.7rem; color:#64748b; margin-inline-start:4px; }
        .od-alert-amount { margin-top:5px; font-weight:800; direction:ltr; unicode-bidi:isolate; font-size:.88rem; }
        .od-low-items {
            margin-top:9px; padding-top:8px; border-top:1px solid rgba(148,163,184,.22);
            display:grid; gap:5px;
        }
        .od-low-item {
            display:grid; grid-template-columns:minmax(0,1fr) auto; gap:7px;
            align-items:center; text-decoration:none!important; padding:4px 6px;
            border-radius:6px; background:rgba(255,255,255,.58);
        }
        .od-low-item:hover { background:#fff; }
        .od-low-name {
            min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
            font-size:.68rem; color:#334155; font-weight:700;
        }
        .od-low-code {
            font-size:.61rem; color:#2563eb; direction:ltr; unicode-bidi:isolate;
        }
        .od-low-more { margin-top:4px; font-size:.64rem; color:#64748b; }
        .alert-red { background:#fff7f7; border-color:#fee2e2; } .alert-red .od-alert-amount { color:#dc2626; }
        .alert-orange { background:#fffaf5; border-color:#ffedd5; } .alert-orange .od-alert-amount { color:#ea580c; }
        .alert-amber { background:#fffdf3; border-color:#fef3c7; }
        .alert-blue { background:#f7fbff; border-color:#dbeafe; } .alert-blue .od-alert-amount { color:#2563eb; }
        .alert-green { background:#f6fff9; border-color:#dcfce7; }

        .od-two { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:18px; }
        .od-panel { padding:14px; overflow:hidden; }
        .od-panel-head { display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:10px; }
        .od-panel-title { font-size:.94rem; font-weight:900; color:#1e293b; }
        .od-table-wrap { overflow:auto; }
        .od-table { width:100%; border-collapse:collapse; min-width:580px; font-size:.76rem; }
        .od-table th { padding:8px; text-align:right; background:#f8fafc; color:#64748b; font-weight:800; border-bottom:1px solid #e2e8f0; }
        .od-table td { padding:9px 8px; border-bottom:1px solid #eef2f7; color:#334155; }
        .od-table tr:last-child td { border-bottom:0; }
        .od-link { color:#2563eb; text-decoration:none; font-weight:700; }
        .money-positive { color:#16a34a!important; font-weight:800; direction:ltr; }
        .money-negative { color:#dc2626!important; font-weight:800; direction:ltr; }

        .od-chart-panel { padding:16px; }
        .od-legend { display:flex; gap:14px; color:#64748b; font-size:.72rem; }
        .od-dot { width:9px; height:9px; display:inline-block; border-radius:50%; margin-left:4px; }
        .od-dot-sales { background:#22c55e; } .od-dot-purchases { background:#3b82f6; }
        .od-chart {
            height:260px; display:grid; grid-template-columns:repeat(6,1fr); gap:12px;
            align-items:end; padding:18px 10px 4px; border-top:1px solid #f1f5f9;
        }
        .od-month { height:100%; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; gap:7px; }
        .od-bars { height:205px; width:72%; display:flex; align-items:flex-end; justify-content:center; gap:7px; }
        .od-bar { width:18px; border-radius:7px 7px 2px 2px; min-height:2px; position:relative; }
        .od-bar-sales { background:#22c55e; } .od-bar-purchases { background:#3b82f6; }
        .od-bar:hover::after {
            content:attr(data-value); position:absolute; bottom:calc(100% + 6px); right:50%; transform:translateX(50%);
            background:#0f172a; color:#fff; padding:4px 6px; border-radius:5px; font-size:.62rem; white-space:nowrap; z-index:3;
        }
        .od-month-label { font-size:.68rem; color:#64748b; white-space:nowrap; }


        .od-v2-title {
            margin:24px 0 14px;
            padding:10px 12px;
            border-right:4px solid #2563eb;
            border-radius:9px;
            background:#f8fbff;
            font-size:1.05rem;
            font-weight:900;
            color:#0f172a;
        }
        .od-analytics-grid {
            display:grid; grid-template-columns:repeat(2,minmax(0,1fr));
            gap:14px; margin-bottom:18px; align-items:start;
        }
        .od-ranking-list { display:grid; gap:7px; }
        .od-rank-row {
            display:grid; grid-template-columns:30px minmax(0,1fr) auto;
            gap:9px; align-items:center; padding:8px 9px;
            border:1px solid #eef2f7; border-radius:9px; background:#fbfdff;
        }
        .od-rank-no {
            width:25px; height:25px; display:flex; align-items:center; justify-content:center;
            border-radius:50%; background:#eef5ff; color:#2563eb; font-weight:900; font-size:.7rem;
        }
        .od-rank-name { min-width:0; }
        .od-rank-name a, .od-rank-name span {
            display:block; color:#1e293b; font-size:.76rem; font-weight:800;
            overflow:hidden; text-overflow:ellipsis; white-space:nowrap; text-decoration:none;
        }
        .od-rank-name small { color:#94a3b8; font-size:.64rem; }
        .od-rank-value {
            text-align:left; direction:ltr; unicode-bidi:isolate; white-space:nowrap;
            font-weight:900; font-size:.76rem; color:#0f172a;
        }
        .od-rank-value small { display:block; color:#94a3b8; font-weight:600; font-size:.6rem; }

        .od-ratio-grid {
            display:grid; grid-template-columns:repeat(2,minmax(0,1fr));
            gap:14px; margin-bottom:18px;
        }
        .od-ratio-card { padding:17px; }
        .od-ratio-head { display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .od-ratio-label { font-weight:900; color:#334155; }
        .od-ratio-percent { font-size:1.35rem; font-weight:900; color:#2563eb; direction:ltr; }
        .od-progress {
            height:9px; border-radius:999px; background:#eef2f7; overflow:hidden; margin:13px 0;
        }
        .od-progress > span {
            display:block; height:100%; border-radius:999px; background:#2563eb;
        }
        .od-ratio-details {
            display:grid; grid-template-columns:repeat(3,1fr); gap:7px; font-size:.66rem;
        }
        .od-ratio-note {
            margin-top:9px; padding:7px 9px; border-radius:8px;
            background:#f8fafc; color:#64748b; font-size:.64rem; line-height:1.6;
        }
        .od-ratio-detail { padding:7px; border-radius:8px; background:#f8fafc; }
        .od-ratio-detail b { display:block; margin-top:3px; font-size:.73rem; direction:ltr; }

        .od-aging-grid {
            display:grid; grid-template-columns:repeat(2,minmax(0,1fr));
            gap:14px; margin-bottom:18px;
        }
        .od-aging-boxes {
            display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:7px;
        }
        .od-aging-box {
            padding:10px 7px; border:1px solid #e2e8f0; border-radius:9px; text-align:center;
        }
        .od-aging-label { font-size:.63rem; color:#64748b; font-weight:800; }
        .od-aging-count { margin-top:5px; font-size:1rem; font-weight:900; color:#0f172a; }
        .od-aging-amount { margin-top:3px; font-size:.62rem; color:#dc2626; direction:ltr; }

        .od-dot-profit { background:#8b5cf6; }
        .od-bar-profit-positive { background:#8b5cf6; }
        .od-bar-profit-negative { background:#ef4444; }


        .od-v3-title {
            margin:26px 0 14px;
            padding:11px 13px;
            border-right:4px solid #0f766e;
            border-radius:9px;
            background:#f0fdfa;
            color:#134e4a;
            font-size:1.05rem;
            font-weight:900;
        }

        .od-management-cards {
            display:grid;
            grid-template-columns:repeat(4,minmax(0,1fr));
            gap:12px;
            margin-bottom:14px;
        }

        .od-management-card {
            border:1px solid #e2e8f0;
            border-radius:14px;
            background:#fff;
            padding:15px;
        }

        .od-management-head {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
        }

        .od-management-label {
            color:#475569;
            font-size:.76rem;
            font-weight:900;
        }

        .od-management-icon {
            font-size:1.1rem;
        }

        .od-management-value {
            margin-top:12px;
            direction:ltr;
            unicode-bidi:isolate;
            font-size:1.18rem;
            font-weight:900;
            color:#0f172a;
        }

        .od-management-note {
            margin-top:5px;
            color:#94a3b8;
            font-size:.62rem;
        }

        .od-v3-grid {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:14px;
            margin-bottom:18px;
        }

        .od-margin-row {
            display:grid;
            grid-template-columns:1fr auto;
            gap:10px;
            align-items:center;
            padding:10px 0;
            border-bottom:1px solid #eef2f7;
        }

        .od-margin-row:last-child { border-bottom:0; }

        .od-margin-label {
            color:#475569;
            font-size:.75rem;
            font-weight:800;
        }

        .od-margin-value {
            text-align:left;
            direction:ltr;
            unicode-bidi:isolate;
            font-weight:900;
        }

        .od-margin-percent {
            display:inline-block;
            min-width:58px;
            margin-inline-start:8px;
            padding:3px 7px;
            border-radius:999px;
            background:#f1f5f9;
            color:#334155;
            font-size:.68rem;
            text-align:center;
        }

        .od-comparison-head {
            display:grid;
            grid-template-columns:1.1fr .9fr .9fr .7fr;
            gap:8px;
            padding:7px 8px;
            background:#f8fafc;
            border-radius:8px;
            color:#64748b;
            font-size:.63rem;
            font-weight:800;
        }

        .od-comparison-row {
            display:grid;
            grid-template-columns:1.1fr .9fr .9fr .7fr;
            gap:8px;
            align-items:center;
            padding:9px 8px;
            border-bottom:1px solid #eef2f7;
            font-size:.69rem;
        }

        .od-change {
            direction:ltr;
            font-weight:900;
            text-align:left;
        }

        .od-change-up { color:#16a34a; }
        .od-change-down { color:#dc2626; }
        .od-change-same { color:#64748b; }

        .od-stock-value-list {
            display:grid;
            gap:7px;
        }

        .od-stock-value-row {
            display:grid;
            grid-template-columns:minmax(0,1fr) auto;
            gap:10px;
            align-items:center;
            padding:8px 9px;
            border:1px solid #eef2f7;
            border-radius:9px;
            background:#fbfdff;
            text-decoration:none!important;
        }

        .od-stock-value-name {
            color:#1e293b;
            font-size:.72rem;
            font-weight:800;
        }

        .od-stock-value-code {
            margin-top:2px;
            color:#94a3b8;
            font-size:.61rem;
            direction:ltr;
            unicode-bidi:isolate;
        }

        .od-stock-value-amount {
            text-align:left;
            direction:ltr;
            unicode-bidi:isolate;
            font-size:.72rem;
            font-weight:900;
            color:#7c3aed;
        }

        .od-stock-value-qty {
            display:block;
            margin-top:2px;
            color:#94a3b8;
            font-size:.58rem;
            font-weight:600;
        }

        @media (max-width:1350px) { .od-cards { grid-template-columns:repeat(3,1fr); } .od-alerts { grid-template-columns:repeat(3,1fr); } }
        @media (max-width:900px) {
            .od-management-cards { grid-template-columns:repeat(2,1fr); }
            .od-v3-grid { grid-template-columns:1fr; }
            .od-cards { grid-template-columns:repeat(2,1fr); }
            .od-alerts { grid-template-columns:repeat(2,1fr); }
            .od-two,.od-analytics-grid,.od-ratio-grid,.od-aging-grid { grid-template-columns:1fr; }
            .od-aging-boxes { grid-template-columns:repeat(2,1fr); }
        }
        @media (max-width:600px) {
            .od-management-cards { grid-template-columns:1fr; }
            .od-cards,.od-alerts { grid-template-columns:1fr; } .od-chart { gap:4px; } .od-bars { width:100%; gap:3px; } .od-bar { width:12px; } }
    </style>

    <div class="octram-dashboard">
        <div class="od-topbar">
            <div class="od-title">
                <h2>نظرة عامة على أداء الشركة</h2>
                <p>أهم مؤشرات التشغيل والحسابات في مكان واحد.</p>
            </div>

            <div class="od-filter">
                <label>الفترة</label>
                <select class="od-select" wire:model.live="period">
                    <option value="today">اليوم</option>
                    <option value="month">هذا الشهر</option>
                    <option value="year">السنة المالية الحالية</option>
                    <option value="custom">فترة مخصصة</option>
                </select>

                @if ($period === 'custom')
                    <div class="od-custom-dates">
                        <div>
                            <span>من</span>
                            <input type="date" wire:model.live="fromDate" class="od-date-input">
                        </div>
                        <div>
                            <span>إلى</span>
                            <input type="date" wire:model.live="toDate" class="od-date-input">
                        </div>
                    </div>
                @endif

                <div class="od-period">{{ $dashboard['period']['from'] }} — {{ $dashboard['period']['to'] }}</div>
            </div>
        </div>

        <div class="od-cards">
            @foreach ($dashboard['cards'] as $card)
                @php $tag = filled($card['url']) ? 'a' : 'div'; @endphp
                <{{ $tag }}
                    @if (filled($card['url'])) href="{{ $card['url'] }}" @endif
                    class="od-card tone-{{ $card['tone'] }}"
                >
                    <div class="od-card-head">
                        <div class="od-card-label">{{ $card['label'] }}</div>
                        <div class="od-card-icon">{{ $card['icon'] }}</div>
                    </div>
                    <div class="od-value">
                        {{ $money($card['value']) }}<span class="od-currency">ج.م</span>
                    </div>

                    @if (! empty($card['sub_lines']))
                        <div class="od-card-sub">
                            @foreach ($card['sub_lines'] as $subLine)
                                <div class="od-card-sub-row">
                                    <span>{{ $subLine['label'] }}</span>
                                    <span class="od-card-sub-value od-card-sub-{{ $subLine['tone'] }}">
                                        {{ $money($subLine['value']) }} ج.م
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (! empty($card['caption']))
                        <div class="od-card-caption">{{ $card['caption'] }}</div>
                    @endif

                    <div class="od-card-foot">{{ $dashboard['period']['label'] }}</div>
                </{{ $tag }}>
            @endforeach
        </div>

        <div class="od-section-title">🔔 التنبيهات والمتابعة</div>

        <div class="od-alerts">
            @foreach ($dashboard['alerts'] as $alert)
                @php
                    $hasItems = ! empty($alert['items']);
                    $tag = (! $hasItems && filled($alert['url'])) ? 'a' : 'div';
                @endphp

                <{{ $tag }}
                    @if (! $hasItems && filled($alert['url'])) href="{{ $alert['url'] }}" @endif
                    class="od-alert alert-{{ $alert['tone'] }}"
                >
                    <div class="od-alert-top">
                        <div class="od-alert-label">{{ $alert['label'] }}</div>
                        <div class="od-alert-icon">{{ $alert['icon'] }}</div>
                    </div>

                    <div class="od-alert-count">
                        {{ $alert['count'] }} <span class="od-alert-unit">{{ $alert['unit'] }}</span>
                    </div>

                    @if ($alert['amount'] !== null)
                        <div class="od-alert-amount">{{ $money($alert['amount']) }} ج.م</div>
                    @endif

                    @if ($hasItems)
                        <div class="od-low-items">
                            @foreach ($alert['items'] as $item)
                                @if ($item['url'])
                                    <a href="{{ $item['url'] }}" class="od-low-item" title="{{ $item['name'] }}">
                                        <span>
                                            <span class="od-low-name">{{ $item['name'] }}</span>
                                            <span class="od-low-code">{{ $item['code'] }}</span>
                                        </span>
                                        <span style="font-size:.62rem;color:#b45309;direction:ltr;">
                                            {{ number_format($item['quantity'], 2) }} / {{ number_format($item['reorder_level'], 2) }}
                                        </span>
                                    </a>
                                @else
                                    <div class="od-low-item">
                                        <span>
                                            <span class="od-low-name">{{ $item['name'] }}</span>
                                            <span class="od-low-code">{{ $item['code'] }}</span>
                                        </span>
                                        <span style="font-size:.62rem;color:#b45309;direction:ltr;">
                                            {{ number_format($item['quantity'], 2) }} / {{ number_format($item['reorder_level'], 2) }}
                                        </span>
                                    </div>
                                @endif
                            @endforeach

                            @if ($alert['count'] > count($alert['items']))
                                <a href="{{ $alert['url'] }}" class="od-low-more">
                                    عرض باقي الأصناف ({{ $alert['count'] - count($alert['items']) }})
                                </a>
                            @endif
                        </div>
                    @endif
                </{{ $tag }}>
            @endforeach
        </div>

        <div class="od-two">
            <section class="od-panel">
                <div class="od-panel-head">
                    <div class="od-panel-title">آخر 5 حركات مالية</div>
                </div>
                <div class="od-table-wrap">
                    <table class="od-table">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>النوع</th>
                                <th>البيان</th>
                                <th>مدين</th>
                                <th>دائن</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($dashboard['recent_financial'] as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td>
                                    @if ($row['url'])
                                        <a class="od-link" href="{{ $row['url'] }}">{{ $row['type'] }}</a>
                                    @else
                                        {{ $row['type'] }}
                                    @endif
                                </td>
                                <td>{{ $row['description'] }}</td>
                                <td class="money-positive">{{ $row['debit'] > 0 ? $money($row['debit']) : '—' }}</td>
                                <td class="money-negative">{{ $row['credit'] > 0 ? $money($row['credit']) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;color:#94a3b8;">لا توجد حركات حديثة.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="od-panel">
                <div class="od-panel-head">
                    <div class="od-panel-title">آخر 5 مستندات تشغيلية</div>
                </div>
                <div class="od-table-wrap">
                    <table class="od-table">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>المستند</th>
                                <th>الرقم</th>
                                <th>العميل / المورد</th>
                                <th>الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($dashboard['recent_documents'] as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td>{{ $row['type'] }}</td>
                                <td>
                                    @if ($row['url'])
                                        <a class="od-link" href="{{ $row['url'] }}">{{ $row['number'] }}</a>
                                    @else
                                        {{ $row['number'] }}
                                    @endif
                                </td>
                                <td>{{ $row['party'] }}</td>
                                <td style="direction:ltr;font-weight:800;">{{ $money($row['total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;color:#94a3b8;">لا توجد مستندات حديثة.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>


        <div class="od-v2-title">📊 تحليلات الأداء — المرحلة الثانية</div>

        <div class="od-ratio-grid">
            @foreach ([
                ['data' => $dashboard['analytics']['collection'], 'note' => $dashboard['analytics']['ratio_notes']['collection']],
                ['data' => $dashboard['analytics']['payment'], 'note' => $dashboard['analytics']['ratio_notes']['payment']],
            ] as $ratioBlock)
                @php $ratio = $ratioBlock['data']; @endphp
                <section class="od-panel od-ratio-card">
                    <div class="od-ratio-head">
                        <div class="od-ratio-label">{{ $ratio['label'] }}</div>
                        <div class="od-ratio-percent">{{ number_format($ratio['percentage'], 1) }}%</div>
                    </div>
                    <div class="od-progress">
                        <span style="width: {{ $ratio['percentage'] }}%;"></span>
                    </div>
                    <div class="od-ratio-details">
                        <div class="od-ratio-detail">
                            الإجمالي
                            <b>{{ $money($ratio['total']) }} ج.م</b>
                        </div>
                        <div class="od-ratio-detail">
                            المسدد / المحصل
                            <b style="color:#059669;">{{ $money($ratio['paid']) }} ج.م</b>
                        </div>
                        <div class="od-ratio-detail">
                            المتبقي
                            <b style="color:#dc2626;">{{ $money($ratio['remaining']) }} ج.م</b>
                        </div>
                    </div>
                    <div class="od-ratio-note">{{ $ratioBlock['note'] }}</div>
                </section>
            @endforeach
        </div>

        <div class="od-analytics-grid">
            <section class="od-panel">
                <div class="od-panel-head"><div class="od-panel-title">أعلى 5 عملاء بالمبيعات</div></div>
                <div class="od-ranking-list">
                    @forelse ($dashboard['analytics']['top_customers'] as $index => $row)
                        <div class="od-rank-row">
                            <div class="od-rank-no">{{ $index + 1 }}</div>
                            <div class="od-rank-name">
                                @if ($row['url'])
                                    <a href="{{ $row['url'] }}">{{ $row['name'] }}</a>
                                @else
                                    <span>{{ $row['name'] }}</span>
                                @endif
                                <small>{{ $row['count'] }} فاتورة</small>
                            </div>
                            <div class="od-rank-value">{{ $money($row['amount']) }}<small>ج.م</small></div>
                        </div>
                    @empty
                        <div style="color:#94a3b8;text-align:center;padding:18px;">لا توجد مبيعات في الفترة.</div>
                    @endforelse
                </div>
            </section>

            <section class="od-panel">
                <div class="od-panel-head"><div class="od-panel-title">أعلى 5 موردين بالمشتريات</div></div>
                <div class="od-ranking-list">
                    @forelse ($dashboard['analytics']['top_suppliers'] as $index => $row)
                        <div class="od-rank-row">
                            <div class="od-rank-no">{{ $index + 1 }}</div>
                            <div class="od-rank-name">
                                @if ($row['url'])
                                    <a href="{{ $row['url'] }}">{{ $row['name'] }}</a>
                                @else
                                    <span>{{ $row['name'] }}</span>
                                @endif
                                <small>{{ $row['count'] }} فاتورة</small>
                            </div>
                            <div class="od-rank-value">{{ $money($row['amount']) }}<small>ج.م</small></div>
                        </div>
                    @empty
                        <div style="color:#94a3b8;text-align:center;padding:18px;">لا توجد مشتريات في الفترة.</div>
                    @endforelse
                </div>
            </section>

            <section class="od-panel">
                <div class="od-panel-head"><div class="od-panel-title">أكثر 5 أصناف مبيعًا</div></div>
                <div class="od-ranking-list">
                    @forelse ($dashboard['analytics']['top_sales_items'] as $index => $row)
                        <div class="od-rank-row">
                            <div class="od-rank-no">{{ $index + 1 }}</div>
                            <div class="od-rank-name">
                                @if ($row['url'])
                                    <a href="{{ $row['url'] }}">{{ $row['name'] }}</a>
                                @else
                                    <span>{{ $row['name'] }}</span>
                                @endif
                                <small>{{ $row['code'] }}</small>
                            </div>
                            <div class="od-rank-value">
                                {{ number_format($row['quantity'], 2) }}
                                <small>{{ $money($row['amount']) }} ج.م</small>
                            </div>
                        </div>
                    @empty
                        <div style="color:#94a3b8;text-align:center;padding:18px;">لا توجد أصناف مباعة في الفترة.</div>
                    @endforelse
                </div>
            </section>

            <section class="od-panel">
                <div class="od-panel-head"><div class="od-panel-title">أكثر 5 أصناف شراءً</div></div>
                <div class="od-ranking-list">
                    @forelse ($dashboard['analytics']['top_purchase_items'] as $index => $row)
                        <div class="od-rank-row">
                            <div class="od-rank-no">{{ $index + 1 }}</div>
                            <div class="od-rank-name">
                                @if ($row['url'])
                                    <a href="{{ $row['url'] }}">{{ $row['name'] }}</a>
                                @else
                                    <span>{{ $row['name'] }}</span>
                                @endif
                                <small>{{ $row['code'] }}</small>
                            </div>
                            <div class="od-rank-value">
                                {{ number_format($row['quantity'], 2) }}
                                <small>{{ $money($row['amount']) }} ج.م</small>
                            </div>
                        </div>
                    @empty
                        <div style="color:#94a3b8;text-align:center;padding:18px;">لا توجد أصناف مشتراة في الفترة.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="od-aging-grid">
            <section class="od-panel">
                <div class="od-panel-head">
                    <div class="od-panel-title">أعمار ديون العملاء</div>
                </div>
                <div class="od-aging-boxes">
                    @foreach ($dashboard['analytics']['customer_aging'] as $bucket)
                        <div class="od-aging-box">
                            <div class="od-aging-label">{{ $bucket['label'] }}</div>
                            <div class="od-aging-count">{{ $bucket['count'] }}</div>
                            <div class="od-aging-amount">{{ $money($bucket['amount']) }} ج.م</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="od-panel">
                <div class="od-panel-head">
                    <div class="od-panel-title">أعمار ديون الموردين</div>
                </div>
                <div class="od-aging-boxes">
                    @foreach ($dashboard['analytics']['supplier_aging'] as $bucket)
                        <div class="od-aging-box">
                            <div class="od-aging-label">{{ $bucket['label'] }}</div>
                            <div class="od-aging-count">{{ $bucket['count'] }}</div>
                            <div class="od-aging-amount">{{ $money($bucket['amount']) }} ج.م</div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>


        <div class="od-v3-title">🏢 الإدارة والسيولة والربحية — المرحلة الثالثة</div>

        <div class="od-management-cards">
            @foreach ($dashboard['management']['cards'] as $card)
                <div class="od-management-card">
                    <div class="od-management-head">
                        <div class="od-management-label">{{ $card['label'] }}</div>
                        <div class="od-management-icon">{{ $card['icon'] }}</div>
                    </div>
                    <div class="od-management-value">
                        {{ $money($card['value']) }} <span class="od-currency">ج.م</span>
                    </div>
                    <div class="od-management-note">{{ $card['note'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="od-v3-grid">
            <section class="od-panel">
                <div class="od-panel-head">
                    <div class="od-panel-title">الربحية من القيود المحاسبية</div>
                </div>

                <div class="od-margin-row">
                    <div class="od-margin-label">الإيرادات</div>
                    <div class="od-margin-value">{{ $money($dashboard['management']['margins']['revenue']) }} ج.م</div>
                </div>

                <div class="od-margin-row">
                    <div class="od-margin-label">مجمل الربح</div>
                    <div class="od-margin-value">
                        {{ $money($dashboard['management']['margins']['gross_profit']) }} ج.م
                        <span class="od-margin-percent">
                            {{ number_format($dashboard['management']['margins']['gross_margin'], 1) }}%
                        </span>
                    </div>
                </div>

                <div class="od-margin-row">
                    <div class="od-margin-label">صافي الربح / الخسارة</div>
                    <div class="od-margin-value">
                        {{ $money($dashboard['management']['margins']['net_profit']) }} ج.م
                        <span class="od-margin-percent">
                            {{ number_format($dashboard['management']['margins']['net_margin'], 1) }}%
                        </span>
                    </div>
                </div>
            </section>

            <section class="od-panel">
                <div class="od-panel-head">
                    <div class="od-panel-title">مقارنة بالفترة السابقة المماثلة</div>
                </div>

                <div style="color:#94a3b8;font-size:.61rem;margin-bottom:8px;">
                    الحالية: {{ $dashboard['management']['comparison']['current_label'] }}
                    — السابقة: {{ $dashboard['management']['comparison']['previous_label'] }}
                </div>

                <div class="od-comparison-head">
                    <span>المؤشر</span>
                    <span>الحالي</span>
                    <span>السابق</span>
                    <span>التغير</span>
                </div>

                @foreach ($dashboard['management']['comparison']['rows'] as $row)
                    <div class="od-comparison-row">
                        <strong>{{ $row['label'] }}</strong>
                        <span style="direction:ltr;">{{ $money($row['current']) }}</span>
                        <span style="direction:ltr;">{{ $money($row['previous']) }}</span>
                        <span class="od-change od-change-{{ $row['direction'] }}">
                            {{ $row['change'] > 0 ? '+' : '' }}{{ number_format($row['change'], 1) }}%
                        </span>
                    </div>
                @endforeach
            </section>

            <section class="od-panel" style="grid-column:1/-1;">
                <div class="od-panel-head">
                    <div class="od-panel-title">أعلى 5 أصناف من حيث قيمة المخزون</div>
                    <div style="color:#94a3b8;font-size:.62rem;">الرصيد × متوسط التكلفة</div>
                </div>

                <div class="od-stock-value-list">
                    @forelse ($dashboard['management']['top_inventory_value'] as $row)
                        @php $stockTag = filled($row['url']) ? 'a' : 'div'; @endphp
                        <{{ $stockTag }}
                            @if (filled($row['url'])) href="{{ $row['url'] }}" @endif
                            class="od-stock-value-row"
                        >
                            <span>
                                <span class="od-stock-value-name">{{ $row['name'] }}</span>
                                <span class="od-stock-value-code">{{ $row['code'] }}</span>
                            </span>

                            <span class="od-stock-value-amount">
                                {{ $money($row['value']) }} ج.م
                                <span class="od-stock-value-qty">
                                    الرصيد: {{ number_format($row['quantity'], 2) }}
                                </span>
                            </span>
                        </{{ $stockTag }}>
                    @empty
                        <div style="text-align:center;color:#94a3b8;padding:18px;">
                            لا توجد أرصدة مخزون حالية.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="od-panel od-chart-panel">
            <div class="od-panel-head">
                <div class="od-panel-title">المبيعات والمشتريات وصافي النتيجة خلال آخر 6 أشهر</div>
                <div class="od-legend">
                    <span><i class="od-dot od-dot-sales"></i>المبيعات</span>
                    <span><i class="od-dot od-dot-purchases"></i>المشتريات</span>
                    <span><i class="od-dot od-dot-profit"></i>صافي الربح/الخسارة</span>
                </div>
            </div>

            <div class="od-chart">
                @foreach ($dashboard['chart'] as $row)
                    <div class="od-month">
                        <div class="od-bars">
                            <div
                                class="od-bar od-bar-sales"
                                data-value="{{ $money($row['sales']) }} ج.م"
                                style="height: {{ max(1, (($row['sales'] / $chartMax) * 100)) }}%;"
                            ></div>
                            <div
                                class="od-bar od-bar-purchases"
                                data-value="{{ $money($row['purchases']) }} ج.م"
                                style="height: {{ max(1, (($row['purchases'] / $chartMax) * 100)) }}%;"
                            ></div>
                            <div
                                class="od-bar {{ ($row['profit'] ?? 0) >= 0 ? 'od-bar-profit-positive' : 'od-bar-profit-negative' }}"
                                data-value="{{ ($row['profit'] ?? 0) >= 0 ? 'ربح' : 'خسارة' }} {{ $money(abs($row['profit'] ?? 0)) }} ج.م"
                                style="height: {{ max(1, ((abs($row['profit'] ?? 0) / $chartMax) * 100)) }}%;"
                            ></div>
                        </div>
                        <div class="od-month-label">{{ $row['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-panels::page>
