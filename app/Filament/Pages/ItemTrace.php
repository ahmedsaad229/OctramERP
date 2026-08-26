<?php

namespace App\Filament\Pages;

use App\Models\Item;
use App\Services\ItemTraceService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemTrace extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'تتبّع الصنف';
    protected static ?string $title = 'تتبّع الصنف';
    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';
    protected static ?int $navigationSort = 15;
    protected string $view = 'filament.pages.item-trace';

    /** @var array<string, mixed> */
    public array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $report = null;

    public bool $hasRun = false;

    public function mount(): void
    {
        $this->form->fill([
            'document_type' => ItemTraceService::ALL,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('البحث')->description('اختر الصنف والمكان الذي تريد البحث داخله.')->compact()->schema([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                    Select::make('item_id')
                        ->label('الصنف')
                        ->options(fn (): array => Item::query()->orderBy('name')->get()
                            ->mapWithKeys(fn (Item $item): array => [
                                $item->getKey() => "{$item->name} — {$item->code}",
                            ])->all())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),

                    Select::make('document_type')
                        ->label('مكان البحث')
                        ->options(fn (): array => app(ItemTraceService::class)->documentTypeOptions())
                        ->default(ItemTraceService::ALL)
                        ->required()
                        ->native(false),

                    DatePicker::make('from_date')
                        ->label('من تاريخ')
                        ->native(false)->locale('ar')->displayFormat('d/m/Y')->format('Y-m-d')
                        ->closeOnDateSelection()->firstDayOfWeek(6),

                    DatePicker::make('to_date')
                        ->label('إلى تاريخ')
                        ->native(false)->locale('ar')->displayFormat('d/m/Y')->format('Y-m-d')
                        ->closeOnDateSelection()->firstDayOfWeek(6)->afterOrEqual('from_date'),
                ]),
            ]),
        ]);
    }

    public function runSearch(): void
    {
        $state = $this->form->getState();

        $this->report = app(ItemTraceService::class)->search(
            (int) $state['item_id'],
            $state['document_type'] ?? ItemTraceService::ALL,
            $state['from_date'] ?? null,
            $state['to_date'] ?? null,
        );

        $this->hasRun = true;
    }

    public function resetSearch(): void
    {
        $this->form->fill(['document_type' => ItemTraceService::ALL]);
        $this->report = null;
        $this->hasRun = false;
        $this->resetValidation();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ((bool) ($user->is_admin ?? false)) {
            return true;
        }

        return method_exists($user, 'hasPermission')
            ? $user->hasPermission('items.view')
            : true;
    }
}
