<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\InteractsWithInventoryReport;
use App\Models\Category;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\InventoryReportService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryStockBalanceReport extends Page
{
    use InteractsWithInventoryReport;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'تقرير أرصدة المخزون';

    protected static ?string $title = 'تقرير أرصدة المخزون';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?string $navigationParentItem = 'تقارير المخزون';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.inventory-stock-balance-report';

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('عوامل التصفية')->compact()->schema([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                    Select::make('warehouse_id')->label('المخزن')
                        ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->native(false),
                    Select::make('category_id')->label('الفئة')
                        ->options(fn (): array => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->native(false),
                    Select::make('item_id')->label('الصنف')
                        ->options(fn (): array => Item::query()->orderBy('name')->get()
                            ->mapWithKeys(fn (Item $item): array => [$item->getKey() => "{$item->name} — {$item->code}"])->all())
                        ->searchable()->native(false),
                    Toggle::make('has_balance')->label('يوجد رصيد فقط')->default(false),
                ]),
            ]),
        ]);
    }

    protected function reportKey(): string
    {
        return 'balances';
    }

    protected function generateReport(array $filters): array
    {
        return app(InventoryReportService::class)->balances($filters);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermission('inventory_stock_balance_report.view') === true;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('inventory_stock_balance_report.view') === true;
    }
}
