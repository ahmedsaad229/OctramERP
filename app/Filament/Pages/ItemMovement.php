<?php

namespace App\Filament\Pages;

use App\Models\Item;
use App\Models\Warehouse;
use App\Services\ItemMovementService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemMovement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'حركة الصنف';

    protected static ?string $title = 'حركة الصنف';

    protected static string|\UnitEnum|null $navigationGroup = 'المخازن';

    protected static ?string $navigationParentItem = 'عمليات المخزون';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.item-movement';

    /** @var array<string, mixed> */
    public array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $report = null;

    public bool $hasRun = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('عوامل التصفية')->compact()->schema([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 5])->schema([
                    Select::make('item_id')->label('الصنف')
                        ->options(fn (): array => Item::query()->where('active', true)->orderBy('name')->get()
                            ->mapWithKeys(fn (Item $item): array => [$item->getKey() => "{$item->name} — {$item->code}"])->all())
                        ->searchable()->required()->native(false),
                    Select::make('warehouse_id')->label('المخزن')
                        ->options(fn (): array => Warehouse::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->native(false),
                    DatePicker::make('from_date')->label('من تاريخ')->native(false)->locale('ar')
                        ->displayFormat('d/m/Y')->format('Y-m-d')->closeOnDateSelection()->firstDayOfWeek(6),
                    DatePicker::make('to_date')->label('إلى تاريخ')->native(false)->locale('ar')
                        ->displayFormat('d/m/Y')->format('Y-m-d')->closeOnDateSelection()->firstDayOfWeek(6)->afterOrEqual('from_date'),
                    Select::make('transaction_type')->label('نوع الحركة')
                        ->options(fn (): array => app(ItemMovementService::class)->transactionTypeOptions())
                        ->placeholder('كل الأنواع')->native(false),
                ]),
            ]),
        ]);
    }

    public function runReport(): void
    {
        $state = $this->form->getState();
        $this->report = app(ItemMovementService::class)->report(
            (int) $state['item_id'],
            filled($state['warehouse_id'] ?? null) ? (int) $state['warehouse_id'] : null,
            $state['from_date'] ?? null,
            $state['to_date'] ?? null,
            $state['transaction_type'] ?? null,
        );
        $this->hasRun = true;
    }

    public function resetReport(): void
    {
        $this->form->fill();
        $this->report = null;
        $this->hasRun = false;
        $this->resetValidation();
    }

    public function printUrl(): ?string
    {
        if (! $this->hasRun || blank($this->data['item_id'] ?? null)) {
            return null;
        }

        return route('item-movement.print', array_filter([
            'item' => $this->data['item_id'],
            'warehouse' => $this->data['warehouse_id'] ?? null,
            'from_date' => $this->data['from_date'] ?? null,
            'to_date' => $this->data['to_date'] ?? null,
            'transaction_type' => $this->data['transaction_type'] ?? null,
        ], fn ($value): bool => filled($value)));
    }

    public function excelUrl(): ?string
    {
        if (! $this->hasRun || blank($this->data['item_id'] ?? null)) {
            return null;
        }

        return route('item-movement.excel', array_filter([
            'item' => $this->data['item_id'],
            'warehouse' => $this->data['warehouse_id'] ?? null,
            'from_date' => $this->data['from_date'] ?? null,
            'to_date' => $this->data['to_date'] ?? null,
            'transaction_type' => $this->data['transaction_type'] ?? null,
        ], fn ($value): bool => filled($value)));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermission('item_movement.view') === true;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('item_movement.view') === true;
    }
}
