<?php

namespace App\Filament\Resources\DueObligations\Widgets;

use App\Support\ArabicInvoiceCount;
use App\Support\ArabicMoney;
use App\Support\DueObligationSummary;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class DueObligationStats extends StatsOverviewWidget
{
    /**
     * @var array<string, float|int>|null
     */
    private ?array $summary = null;

    protected string $view = 'filament.resources.due-obligations.widgets.stats';

    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

    protected function getStats(): array
    {
        $totals = $this->getSummary();

        return [
            Stat::make('مستحقات العملاء', self::money($totals['customer_due']))
                ->description(ArabicInvoiceCount::credit($totals['customer_due_count']))
                ->icon(Heroicon::OutlinedUsers)
                ->color('success')
                ->extraAttributes(['data-tone' => 'success']),
            Stat::make('التزامات الموردين', self::money($totals['supplier_due']))
                ->description(ArabicInvoiceCount::credit($totals['supplier_due_count']))
                ->icon(Heroicon::OutlinedBuildingStorefront)
                ->color('info')
                ->extraAttributes(['data-tone' => 'info']),
            Stat::make('المستحق اليوم', self::money($totals['due_today']))
                ->description(ArabicInvoiceCount::dueToday($totals['due_today_count']))
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('warning')
                ->extraAttributes(['data-tone' => 'warning']),
            Stat::make('المتأخر', self::money($totals['overdue']))
                ->description(ArabicInvoiceCount::overdue($totals['overdue_count']))
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger')
                ->extraAttributes(['data-tone' => 'danger']),
        ];
    }

    /**
     * @return array<string, float|int>
     */
    public function getSummary(): array
    {
        return $this->summary ??= DueObligationSummary::totals();
    }

    public function getOverdueBannerText(): string
    {
        $summary = $this->getSummary();

        if ($summary['overdue_count'] === 0) {
            return 'لا توجد استحقاقات متأخرة.';
        }

        return 'يوجد '.ArabicInvoiceCount::overdue($summary['overdue_count'])
            .' بإجمالي '.ArabicMoney::format($summary['overdue']);
    }

    private static function money(float $amount): HtmlString
    {
        $formatted = e(number_format($amount, 2));

        return new HtmlString(
            "<span class=\"due-obligation-amount-number\">{$formatted}</span>"
            .'<span class="due-obligation-amount-currency">ج.م</span>',
        );
    }
}
