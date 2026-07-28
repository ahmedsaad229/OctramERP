<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\InteractsWithInventoryReport;
use App\Models\Category;
use App\Models\Warehouse;
use App\Services\InventoryReportService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LowStockReport extends Page
{
    use InteractsWithInventoryReport;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'الأصناف منخفضة الرصيد';

    protected static ?string $title = 'الأصناف منخفضة الرصيد';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?string $navigationParentItem = 'تقارير المخزون';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.low-stock-report';

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('عوامل التصفية')->compact()->schema([
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    Select::make('warehouse_id')->label('المخزن')
                        ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->native(false),
                    Select::make('category_id')->label('الفئة')
                        ->options(fn (): array => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->native(false),
                    Select::make('status')->label('الحالة')
                        ->options(fn (): array => app(InventoryReportService::class)->statusOptions())
                        ->native(false),
                ]),
            ]),
        ]);
    }

    protected function reportKey(): string
    {
        return 'low-stock';
    }

    protected function generateReport(array $filters): array
    {
        return app(InventoryReportService::class)->lowStock($filters);
    }
}
