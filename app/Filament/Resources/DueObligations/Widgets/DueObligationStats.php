<?php

namespace App\Filament\Resources\DueObligations\Widgets;

use App\Support\DueObligationSummary;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DueObligationStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totals = DueObligationSummary::totals();

        return [
            Stat::make('مستحقات العملاء', self::money($totals['customer_due'])),
            Stat::make('التزامات الموردين', self::money($totals['supplier_due'])),
            Stat::make('المستحق اليوم', self::money($totals['due_today'])),
            Stat::make('المتأخر', self::money($totals['overdue'])),
        ];
    }

    private static function money(float $amount): string
    {
        return number_format($amount, 2).' EGP';
    }
}
