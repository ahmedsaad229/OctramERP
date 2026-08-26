<?php

namespace App\Filament\Pages;

use App\Models\Supplier;
use App\Services\SupplierStatementService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierStatement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'كشف حساب الموردين';

    protected static ?string $title = 'كشف حساب مورد';

    protected static string|\UnitEnum|null $navigationGroup = 'المشتريات';

    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.pages.supplier-statement';

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
            Section::make('عوامل التصفية')
                ->description('اختر المورد والفترة، ثم اضغط عرض الكشف.')
                ->compact()
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                        Select::make('supplier_id')
                            ->label('المورد')
                            ->options(fn (): array => Supplier::query()
                                ->where('active', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Supplier $supplier): array => [
                                    $supplier->getKey() => trim("{$supplier->name} — {$supplier->code}", ' —'),
                                ])->all())
                            ->searchable()
                            ->required()
                            ->native(false),
                        DatePicker::make('from_date')->label('من تاريخ')
                            ->native(false)->locale('ar')->displayFormat('d/m/Y')->format('Y-m-d')
                            ->closeOnDateSelection()->firstDayOfWeek(6),
                        DatePicker::make('to_date')->label('إلى تاريخ')
                            ->native(false)->locale('ar')->displayFormat('d/m/Y')->format('Y-m-d')
                            ->closeOnDateSelection()->firstDayOfWeek(6)->afterOrEqual('from_date'),
                        Select::make('transaction_type')
                            ->label('نوع الحركة')
                            ->options(fn (): array => app(SupplierStatementService::class)->transactionTypeOptions())
                            ->placeholder('كل الأنواع')
                            ->native(false),
                    ]),
                ]),
        ]);
    }

    public function runReport(): void
    {
        $state = $this->form->getState();
        $this->report = app(SupplierStatementService::class)->report(
            (int) $state['supplier_id'],
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
        if (! $this->hasRun || blank($this->data['supplier_id'] ?? null)) {
            return null;
        }

        return route('supplier-statement.print', array_filter([
            'supplier' => $this->data['supplier_id'],
            'from_date' => $this->data['from_date'] ?? null,
            'to_date' => $this->data['to_date'] ?? null,
            'transaction_type' => $this->data['transaction_type'] ?? null,
        ], fn ($value): bool => filled($value)));
    }

    public function excelUrl(): ?string
    {
        if (! $this->hasRun || blank($this->data['supplier_id'] ?? null)) {
            return null;
        }

        return route('supplier-statement.excel', array_filter([
            'supplier' => $this->data['supplier_id'],
            'from_date' => $this->data['from_date'] ?? null,
            'to_date' => $this->data['to_date'] ?? null,
            'transaction_type' => $this->data['transaction_type'] ?? null,
        ], fn ($value): bool => filled($value)));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermission('supplier_statements.view') === true;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('supplier_statements.view') === true;
    }
}
