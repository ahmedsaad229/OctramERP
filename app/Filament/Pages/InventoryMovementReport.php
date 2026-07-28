<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\InteractsWithInventoryReport;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\InventoryReportService;
use App\Services\ItemMovementService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryMovementReport extends Page
{
    use InteractsWithInventoryReport;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'تقرير حركة المخزون';

    protected static ?string $title = 'تقرير حركة المخزون';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?string $navigationParentItem = 'تقارير المخزون';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.inventory-movement-report';

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('عوامل التصفية')->compact()->schema([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 6])->schema([
                    DatePicker::make('from_date')->label('من تاريخ')->native(false)->locale('ar')
                        ->displayFormat('d/m/Y')->format('Y-m-d')->closeOnDateSelection()->firstDayOfWeek(6),
                    DatePicker::make('to_date')->label('إلى تاريخ')->native(false)->locale('ar')
                        ->displayFormat('d/m/Y')->format('Y-m-d')->closeOnDateSelection()->firstDayOfWeek(6)
                        ->afterOrEqual('from_date'),
                    Select::make('item_id')->label('الصنف')
                        ->options(fn (): array => Item::query()->orderBy('name')->get()
                            ->mapWithKeys(fn (Item $item): array => [$item->getKey() => "{$item->name} — {$item->code}"])->all())
                        ->searchable()->native(false),
                    Select::make('warehouse_id')->label('المخزن')
                        ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->native(false),
                    Select::make('transaction_type')->label('نوع الحركة')
                        ->options(fn (): array => app(ItemMovementService::class)->transactionTypeOptions())
                        ->native(false),
                    TextInput::make('reference_no')->label('رقم المستند')->maxLength(255),
                ]),
            ]),
        ]);
    }

    protected function reportKey(): string
    {
        return 'movements';
    }

    protected function generateReport(array $filters): array
    {
        return app(InventoryReportService::class)->movements($filters);
    }
}
