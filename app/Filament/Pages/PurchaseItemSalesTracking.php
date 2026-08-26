<?php

namespace App\Filament\Pages;

use App\Models\Item;
use App\Models\Supplier;
use App\Services\PurchaseItemSalesTrackingService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseItemSalesTracking extends Page
{
    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel =
        'متابعة بيع أصناف المشتريات';

    protected static ?string $title =
        'متابعة بيع أصناف المشتريات';

    protected static string|\UnitEnum|null $navigationGroup =
        'المشتريات';

    protected static ?int $navigationSort = 40;

    protected string $view =
        'filament.pages.purchase-item-sales-tracking';

    public array $data = [];

    public ?array $report = null;

    public function mount(): void
    {
        $this->form->fill([]);

        $this->runReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([

                Section::make('البحث والتصفية')
                    ->compact()
                    ->schema([

                        Grid::make([
                            'default' => 1,
                            'md' => 3,
                            'xl' => 5,
                        ])
                        ->schema([

                            Select::make('supplier_id')
                                ->label('المورد')
                                ->options(
                                    fn (): array =>
                                        Supplier::query()
                                            ->where('active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all()
                                )
                                ->searchable()
                                ->preload(),

                            Select::make('item_id')
                                ->label('الصنف')
                                ->options(
                                    fn (): array =>
                                        Item::query()
                                            ->where('active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all()
                                )
                                ->searchable()
                                ->preload(),

                            Select::make('status')
                                ->label('حالة البيع')
                                ->options([
                                    'not_sold' => 'لم يتم البيع',
                                    'partially_sold' => 'بيع جزئي',
                                    'fully_sold' => 'تم البيع بالكامل',
                                ])
                                ->native(false),

                            DatePicker::make('from_date')
                                ->label('من تاريخ شراء')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d'),

                            DatePicker::make('to_date')
                                ->label('إلى تاريخ شراء')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d')
                                ->afterOrEqual('from_date'),
                        ]),
                    ]),
            ]);
    }

    public function runReport(): void
    {
        $this->report = app(
            PurchaseItemSalesTrackingService::class
        )->report(
            $this->form->getState()
        );
    }


    public function printUrl(): string
    {
        return route(
            'purchase-item-sales-tracking.print',
            array_filter(
                $this->data,
                fn ($value): bool => $value !== null && $value !== ''
            )
        );
    }

    public function excelUrl(): string
    {
        return route(
            'purchase-item-sales-tracking.excel',
            array_filter(
                $this->data,
                fn ($value): bool => $value !== null && $value !== ''
            )
        );
    }
    public function resetFilters(): void
    {
        $this->data = [];

        $this->form->fill([]);

        $this->runReport();
    }
}
