@php
    $pollingInterval = $this->getPollingInterval();
@endphp

<x-filament-widgets::widget
    :attributes="
        (new \Filament\Support\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => $pollingInterval ? true : null,
            ], escape: false)
            ->class([
                'fi-wi-stats-overview',
                'due-obligation-stats',
            ])
    "
>
    {{ $this->content }}

    @php
        $summary = $this->getSummary();
        $hasOverdue = $summary['overdue_count'] > 0;
    @endphp

    <div
        @class([
            'due-obligation-alert',
            'due-obligation-alert-danger' => $hasOverdue,
            'due-obligation-alert-success' => ! $hasOverdue,
        ])
        role="status"
    >
        <x-filament::icon
            :icon="$hasOverdue
                ? \Filament\Support\Icons\Heroicon::OutlinedExclamationTriangle
                : \Filament\Support\Icons\Heroicon::OutlinedCheckCircle"
        />

        <span>{{ $this->getOverdueBannerText() }}</span>
    </div>

    <style>
        .due-obligation-stats .fi-section-content {
            padding-block: .65rem;
        }

        .due-obligation-stats .fi-wi-stats-overview-stat-content {
            gap: .35rem;
            padding: .75rem 1rem;
        }

        .due-obligation-stats .fi-wi-stats-overview-stat-label-ctn {
            gap: .65rem;
        }

        .due-obligation-stats .fi-wi-stats-overview-stat-label-ctn > svg {
            box-sizing: content-box;
            width: 1.25rem;
            height: 1.25rem;
            padding: .45rem;
            border-radius: 9999px;
        }

        .due-obligation-stats [data-tone="success"] .fi-wi-stats-overview-stat-label-ctn > svg {
            color: rgb(22 163 74);
            background: rgb(240 253 244);
        }

        .due-obligation-stats [data-tone="info"] .fi-wi-stats-overview-stat-label-ctn > svg {
            color: rgb(37 99 235);
            background: rgb(239 246 255);
        }

        .due-obligation-stats [data-tone="warning"] .fi-wi-stats-overview-stat-label-ctn > svg {
            color: rgb(217 119 6);
            background: rgb(255 251 235);
        }

        .due-obligation-stats [data-tone="danger"] .fi-wi-stats-overview-stat-label-ctn > svg {
            color: rgb(220 38 38);
            background: rgb(254 242 242);
        }

        .due-obligation-stats .fi-wi-stats-overview-stat-value {
            display: flex;
            align-items: baseline;
            gap: .35rem;
            line-height: 1.15;
        }

        .due-obligation-stats .due-obligation-amount-number {
            font-weight: 750;
        }

        .due-obligation-stats .due-obligation-amount-currency {
            font-size: .7em;
            font-weight: 500;
            color: rgb(107 114 128);
        }

        .due-obligation-stats .fi-wi-stats-overview-stat-description {
            margin-top: .1rem;
            color: rgb(107 114 128);
        }

        .due-obligation-alert {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-top: .65rem;
            padding: .6rem .85rem;
            border: 1px solid;
            border-radius: .75rem;
            font-size: .875rem;
            line-height: 1.4;
        }

        .due-obligation-alert > svg {
            width: 1.15rem;
            height: 1.15rem;
            flex: none;
        }

        .due-obligation-alert-danger {
            color: rgb(185 28 28);
            border-color: rgb(254 202 202);
            background: rgb(254 242 242);
        }

        .due-obligation-alert-success {
            color: rgb(21 128 61);
            border-color: rgb(187 247 208);
            background: rgb(240 253 244);
        }

        .fi-resource-due-obligations .fi-tabs {
            justify-self: stretch;
        }

        .fi-resource-due-obligations .fi-tabs-list {
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .fi-resource-due-obligations .fi-ta-header-toolbar {
            gap: .75rem;
        }

        .fi-resource-due-obligations .fi-ta-cell {
            padding-block: .85rem;
            vertical-align: middle;
        }

        .fi-resource-due-obligations tr.due-obligation-row-overdue > td {
            background-color: rgb(254 242 242 / .55);
        }

        .fi-resource-due-obligations tr.due-obligation-row-today > td {
            background-color: rgb(255 251 235 / .6);
        }

        .fi-resource-due-obligations tr.due-obligation-row-cash > td {
            background-color: rgb(249 250 251 / .65);
        }

        .fi-resource-due-obligations .fi-ta {
            overflow-x: auto;
        }

        @media (prefers-color-scheme: dark) {
            .due-obligation-stats [data-tone] .fi-wi-stats-overview-stat-label-ctn > svg {
                background: rgb(255 255 255 / .08);
            }

            .due-obligation-alert-danger,
            .due-obligation-alert-success {
                background: rgb(255 255 255 / .04);
            }

            .fi-resource-due-obligations tr.due-obligation-row-overdue > td {
                background-color: rgb(127 29 29 / .14);
            }

            .fi-resource-due-obligations tr.due-obligation-row-today > td {
                background-color: rgb(120 53 15 / .12);
            }

            .fi-resource-due-obligations tr.due-obligation-row-cash > td {
                background-color: rgb(255 255 255 / .025);
            }
        }

        @media (max-width: 640px) {
            .due-obligation-stats .fi-wi-stats-overview-stat-content {
                padding: .65rem .8rem;
            }
        }
    </style>
</x-filament-widgets::widget>
