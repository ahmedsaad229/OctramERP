<x-filament-panels::page>
    @php
        $hour = now()->hour;
        $dayGreeting = $hour < 12 ? 'صباح الخير' : 'مساء الخير';
        $dayMessage = $hour < 12
            ? 'بداية موفقة ويوم عمل مثمر بإذن الله.'
            : ($hour < 18
                ? 'نتمنى لك استكمال يوم عمل ناجح ومنظم.'
                : 'نتمنى لك مساءً هادئًا ونهاية يوم موفقة.');

        $personalHome = app(\App\Services\WelcomeHomeService::class)->forUser($user);
    @endphp

    <style>
        .octram-welcome {
            direction: rtl;
            position: relative;
            overflow: hidden;
            min-height: 72vh;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            background:
                radial-gradient(circle at 12% 14%, rgba(37,99,235,.10), transparent 27%),
                radial-gradient(circle at 88% 82%, rgba(15,118,110,.08), transparent 25%),
                linear-gradient(135deg, #ffffff 0%, #f8fbff 56%, #f8fafc 100%);
            padding: clamp(22px, 4vw, 48px);
        }

        .octram-welcome::before,
        .octram-welcome::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            opacity: .45;
            filter: blur(2px);
            animation: octramFloat 9s ease-in-out infinite;
        }

        .octram-welcome::before {
            width: 180px;
            height: 180px;
            left: -70px;
            top: 18%;
            border: 1px solid rgba(37,99,235,.22);
        }

        .octram-welcome::after {
            width: 260px;
            height: 260px;
            right: -110px;
            bottom: -90px;
            border: 1px solid rgba(15,118,110,.18);
            animation-delay: -3s;
        }

        .ow-hero {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1.5fr .7fr;
            gap: 28px;
            align-items: center;
            margin-bottom: 34px;
        }

        .ow-copy {
            animation: octramEnter .65s ease both;
        }

        .ow-greeting {
            margin-bottom: 4px;
            color: #64748b;
            font-size: .88rem;
            font-weight: 800;
            animation: octramEnter .55s ease both;
        }

        .ow-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #22c55e;
            box-shadow: 0 0 0 5px rgba(34,197,94,.10);
            animation: octramPulse 2s ease-in-out infinite;
        }

        .ow-brand-caption {
            margin-top: 11px;
            color: #64748b;
            font-size: .72rem;
            font-weight: 800;
            text-align: center;
        }

        .ow-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 11px;
            border: 1px solid #dbeafe;
            border-radius: 999px;
            background: rgba(239,246,255,.86);
            color: #1d4ed8;
            font-size: .76rem;
            font-weight: 800;
            margin-bottom: 13px;
        }

        .ow-title {
            margin: 0;
            color: #0f172a;
            font-size: clamp(1.8rem, 4vw, 3rem);
            font-weight: 900;
            letter-spacing: -.02em;
        }

        .ow-name {
            color: #1559b7;
        }

        .ow-subtitle {
            margin: 13px 0 0;
            color: #64748b;
            font-size: 1rem;
            max-width: 720px;
        }

        .ow-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 18px;
        }

        .ow-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: rgba(255,255,255,.8);
            color: #475569;
            font-size: .76rem;
        }

        .ow-brand {
            animation: octramLogoIn .8s cubic-bezier(.2,.8,.2,1) both;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
        }

        .ow-logo-card {
            width: min(240px, 100%);
            min-height: 170px;
            border: 1px solid #dbe4ef;
            border-radius: 22px;
            background: rgba(255,255,255,.82);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .ow-logo {
            max-width: 190px;
            max-height: 115px;
            object-fit: contain;
        }

        .ow-fallback-logo {
            width: 92px;
            height: 92px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 24px;
            background: #123b67;
            color: #fff;
            font-size: 2rem;
            font-weight: 900;
        }

        .ow-section-head {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 13px;
        }

        .ow-section-title {
            margin: 0;
            color: #1e293b;
            font-size: 1rem;
            font-weight: 900;
        }

        .ow-section-note {
            color: #94a3b8;
            font-size: .72rem;
        }

        .ow-grid {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .ow-link {
            --delay: 0ms;
            min-height: 132px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: rgba(255,255,255,.88);
            text-decoration: none !important;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            animation: octramCardIn .55s ease both;
            animation-delay: var(--delay);
        }

        .ow-link:hover {
            transform: translateY(-4px);
            border-color: #9fc4f0;
            box-shadow: 0 14px 28px rgba(15,23,42,.09);
            background: #ffffff;
        }

        .ow-link-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .ow-icon {
            width: 40px;
            height: 40px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            font-size: 1.15rem;
        }

        .ow-card-index {
            margin-inline-start: auto;
            color: #cbd5e1;
            font-size: .62rem;
            font-weight: 900;
            letter-spacing: .05em;
        }

        .ow-arrow {
            color: #94a3b8;
            font-size: 1rem;
            transition: transform .18s ease;
        }

        .ow-link:hover .ow-arrow {
            transform: translateX(-3px);
            color: #2563eb;
        }

        .ow-label {
            margin-top: 13px;
            color: #1e293b;
            font-size: .84rem;
            font-weight: 900;
        }

        .ow-description {
            margin-top: 4px;
            color: #94a3b8;
            font-size: .68rem;
            line-height: 1.55;
        }

        .ow-empty {
            grid-column: 1 / -1;
            padding: 25px;
            text-align: center;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            color: #64748b;
            background: rgba(255,255,255,.7);
        }


        .ow-personal-grid {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: .9fr 1.1fr;
            gap: 14px;
            margin-top: 20px;
        }

        .ow-personal-panel {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: rgba(255,255,255,.86);
            padding: 15px;
        }

        .ow-personal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 11px;
        }

        .ow-personal-title {
            color: #1e293b;
            font-size: .84rem;
            font-weight: 900;
        }

        .ow-personal-hint {
            color: #94a3b8;
            font-size: .63rem;
        }

        .ow-task-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .ow-task {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px;
            border: 1px solid #edf2f7;
            border-radius: 11px;
            background: #fbfdff;
            text-decoration: none !important;
            transition: .16s ease;
        }

        .ow-task:hover {
            border-color: #bdd3ef;
            background: #fff;
            transform: translateY(-2px);
        }

        .ow-task-icon {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eff6ff;
        }

        .ow-task-label {
            color: #475569;
            font-size: .67rem;
            font-weight: 800;
        }

        .ow-task-value {
            margin-top: 2px;
            color: #0f172a;
            font-size: .92rem;
            font-weight: 900;
        }

        .ow-recent-list {
            display: grid;
            gap: 6px;
        }

        .ow-recent-row {
            display: grid;
            grid-template-columns: 34px minmax(0,1fr) auto;
            align-items: center;
            gap: 9px;
            padding: 8px 9px;
            border-bottom: 1px solid #eef2f7;
            text-decoration: none !important;
        }

        .ow-recent-row:last-child {
            border-bottom: 0;
        }

        .ow-recent-row:hover {
            background: #f8fbff;
            border-radius: 9px;
        }

        .ow-recent-icon {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: #f1f5f9;
        }

        .ow-recent-main {
            min-width: 0;
        }

        .ow-recent-top {
            display: flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
        }

        .ow-recent-type {
            color: #64748b;
            font-size: .61rem;
        }

        .ow-recent-number {
            color: #2563eb;
            font-size: .69rem;
            font-weight: 900;
            direction: ltr;
            unicode-bidi: isolate;
        }

        .ow-recent-party {
            margin-top: 2px;
            color: #334155;
            font-size: .69rem;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ow-recent-time {
            color: #94a3b8;
            font-size: .6rem;
            white-space: nowrap;
        }

        .ow-personal-empty {
            padding: 18px 10px;
            text-align: center;
            color: #94a3b8;
            font-size: .69rem;
        }

        .ow-footer {
            position: relative;
            z-index: 2;
            margin-top: 26px;
            padding-top: 15px;
            border-top: 1px solid #e8eef5;
            color: #94a3b8;
            font-size: .68rem;
            text-align: center;
        }

        @keyframes octramEnter {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes octramLogoIn {
            from { opacity: 0; transform: scale(.92) rotate(-2deg); }
            to { opacity: 1; transform: scale(1) rotate(0); }
        }

        @keyframes octramCardIn {
            from { opacity: 0; transform: translateY(16px) scale(.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes octramFloat {
            0%,100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(6deg); }
        }

        @keyframes octramPulse {
            0%,100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(.82); opacity: .7; }
        }

        @media (prefers-reduced-motion: reduce) {
            .octram-welcome *,
            .octram-welcome::before,
            .octram-welcome::after {
                animation: none !important;
                transition: none !important;
            }
        }

        @media (max-width: 1280px) {
            .ow-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 850px) {
            .ow-personal-grid { grid-template-columns: 1fr; }
            .ow-hero { grid-template-columns: 1fr; }
            .ow-brand { order: -1; min-height: 120px; }
            .ow-logo-card { min-height: 120px; }
            .ow-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 520px) {
            .ow-task-grid { grid-template-columns: 1fr; }
            .octram-welcome { padding: 18px; border-radius: 18px; }
            .ow-grid { grid-template-columns: 1fr; }
            .ow-section-head { align-items: start; flex-direction: column; }
        }
    </style>

    <div class="octram-welcome">
        <section class="ow-hero">
            <div class="ow-copy">
                <div class="ow-kicker">
                    <span class="ow-status-dot"></span>
                    Octram ERP جاهز للعمل
                </div>

                <div class="ow-greeting">{{ $dayGreeting }}</div>

                <h1 class="ow-title">
                    أهلاً بك،
                    <span class="ow-name">{{ $user?->name ?? 'مستخدم أوكترام' }}</span>
                </h1>

                <p class="ow-subtitle">
                    {{ $dayMessage }} اختر من الاختصارات المتاحة لك وابدأ عملك مباشرة.
                </p>

                <div class="ow-meta">
                    <span class="ow-chip">📅 {{ $todayLabel }}</span>

                    @if (filled($user?->job_title))
                        <span class="ow-chip">💼 {{ $user->job_title }}</span>
                    @endif

                    @if ($user?->role)
                        <span class="ow-chip">🔐 {{ $user->role->name }}</span>
                    @elseif ($user?->is_admin)
                        <span class="ow-chip">🔐 مدير النظام</span>
                    @endif
                </div>
            </div>

            <div class="ow-brand">
                <div class="ow-logo-card">
                    @if ($settings->logoUrl())
                        <img
                            src="{{ $settings->logoUrl() }}"
                            alt="{{ $settings->commercialName() }}"
                            class="ow-logo"
                        >
                    @else
                        <div class="ow-fallback-logo">O</div>
                    @endif
                    <div class="ow-brand-caption">{{ $settings->commercialName() }}</div>
                </div>
            </div>
        </section>

        <div class="ow-section-head">
            <h2 class="ow-section-title">الوصول السريع</h2>
            <div class="ow-section-note">تظهر لك فقط الشاشات المسموح بها حسب صلاحياتك</div>
        </div>

        <section class="ow-grid">
            @forelse ($quickLinks as $index => $link)
                <a
                    href="{{ $link['url'] }}"
                    class="ow-link"
                    style="--delay: {{ min($index * 70, 560) }}ms;"
                >
                    <div class="ow-link-top">
                        <span class="ow-icon">{{ $link['icon'] }}</span>
                        <span class="ow-card-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="ow-arrow">←</span>
                    </div>

                    <div>
                        <div class="ow-label">{{ $link['label'] }}</div>
                        <div class="ow-description">{{ $link['description'] }}</div>
                    </div>
                </a>
            @empty
                <div class="ow-empty">
                    لا توجد اختصارات متاحة لحسابك حاليًا. راجع مدير النظام لتحديد صلاحياتك.
                </div>
            @endforelse
        </section>


        <section class="ow-personal-grid">
            <div class="ow-personal-panel">
                <div class="ow-personal-head">
                    <div class="ow-personal-title">✅ مهامي اليوم</div>
                    <div class="ow-personal-hint">متابعة سريعة لما يخص حسابك</div>
                </div>

                <div class="ow-task-grid">
                    @forelse ($personalHome['tasks'] as $task)
                        @php $taskTag = filled($task['url']) ? 'a' : 'div'; @endphp
                        <{{ $taskTag }}
                            @if (filled($task['url'])) href="{{ $task['url'] }}" @endif
                            class="ow-task"
                        >
                            <span class="ow-task-icon">{{ $task['icon'] }}</span>
                            <span>
                                <span class="ow-task-label">{{ $task['label'] }}</span>
                                <span class="ow-task-value">
                                    {{ $task['value'] }} {{ $task['unit'] }}
                                </span>
                            </span>
                        </{{ $taskTag }}>
                    @empty
                        <div class="ow-personal-empty" style="grid-column:1/-1;">
                            لا توجد مهام تحتاج متابعة حاليًا 🎉
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="ow-personal-panel">
                <div class="ow-personal-head">
                    <div class="ow-personal-title">🕘 آخر ما عملت عليه</div>
                    <div class="ow-personal-hint">آخر 5 مستندات مرتبطة بحسابك</div>
                </div>

                <div class="ow-recent-list">
                    @forelse ($personalHome['recent'] as $row)
                        @php $recentTag = filled($row['url']) ? 'a' : 'div'; @endphp
                        <{{ $recentTag }}
                            @if (filled($row['url'])) href="{{ $row['url'] }}" @endif
                            class="ow-recent-row"
                        >
                            <span class="ow-recent-icon">{{ $row['icon'] }}</span>

                            <span class="ow-recent-main">
                                <span class="ow-recent-top">
                                    <span class="ow-recent-type">{{ $row['type'] }}</span>
                                    <span class="ow-recent-number">{{ $row['number'] }}</span>
                                </span>
                                <span class="ow-recent-party">{{ $row['party'] }}</span>
                            </span>

                            <span class="ow-recent-time">{{ $row['time'] }}</span>
                        </{{ $recentTag }}>
                    @empty
                        <div class="ow-personal-empty">
                            لا توجد مستندات حديثة مرتبطة بحسابك.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <div class="ow-footer">
            {{ $settings->commercialName() }} — نظام Octram ERP
        </div>
    </div>
</x-filament-panels::page>
