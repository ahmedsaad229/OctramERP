<?php

namespace App\Filament\Resources\DueObligations\Widgets;

use App\Support\ArabicMoney;
use App\Support\DueObligationSummary;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DueObligationStats extends StatsOverviewWidget
{
    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

    protected function getStats(): array
    {
        $totals = DueObligationSummary::totals();

        return [
            Stat::make('مستحقات العملاء', ArabicMoney::format($totals['customer_due']))
                ->description('الفواتير الآجلة فقط')
                ->icon(Heroicon::OutlinedUsers)
                ->color('success'),
            Stat::make('التزامات الموردين', ArabicMoney::format($totals['supplier_due']))
                ->description('الفواتير الآجلة فقط')
                ->icon(Heroicon::OutlinedBuildingStorefront)
                ->color('info'),
            Stat::make('المستحق اليوم', ArabicMoney::format($totals['due_today']))
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('warning'),
            Stat::make('المتأخر', ArabicMoney::format($totals['overdue']))
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),
        ];
    }
}
