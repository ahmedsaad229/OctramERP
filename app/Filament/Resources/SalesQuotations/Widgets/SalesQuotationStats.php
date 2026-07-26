<?php

namespace App\Filament\Resources\SalesQuotations\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SalesQuotationStats extends StatsOverviewWidget
{
    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 5,
    ];

    protected function getStats(): array
    {
        $base = DB::table('sales_quotations as quotations');
        $fullyConverted = $this->conversionQuery(true)->count();
        $partiallyConverted = $this->conversionQuery(false)->count();

        return [
            Stat::make('إجمالي عروض الأسعار', (clone $base)->count())
                ->icon('heroicon-o-document-text')
                ->color('gray'),
            Stat::make('العروض السارية', (clone $base)
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', today()))
                ->count())
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('محول جزئيًا', $partiallyConverted)
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('warning'),
            Stat::make('محول بالكامل', $fullyConverted)
                ->icon('heroicon-o-check-badge')
                ->color('info'),
            Stat::make('منتهي الصلاحية', (clone $base)->whereDate('valid_until', '<', today())->count())
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }

    private function conversionQuery(bool $fullyConverted): Builder
    {
        $remainingExists = <<<'SQL'
exists (
    select 1 from sales_quotation_items as quotation_items
    where quotation_items.sales_quotation_id = quotations.id
      and quotation_items.quantity > coalesce((
          select sum(invoice_items.quantity)
          from sales_invoice_items as invoice_items
          where invoice_items.sales_quotation_item_id = quotation_items.id
      ), 0)
)
SQL;
        $invoicedExists = <<<'SQL'
exists (
    select 1 from sales_invoice_items as invoice_items
    inner join sales_quotation_items as quotation_items
        on quotation_items.id = invoice_items.sales_quotation_item_id
    where quotation_items.sales_quotation_id = quotations.id
)
SQL;

        return DB::table('sales_quotations as quotations')
            ->whereExists(fn (Builder $query): Builder => $query
                ->selectRaw('1')
                ->from('sales_quotation_items')
                ->whereColumn('sales_quotation_items.sales_quotation_id', 'quotations.id'))
            ->whereRaw($invoicedExists)
            ->whereRaw(($fullyConverted ? 'not ' : '').$remainingExists);
    }
}
