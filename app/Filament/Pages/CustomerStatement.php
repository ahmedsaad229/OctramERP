<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\CustomerStatementService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerStatement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'كشف حساب العملاء';

    protected static ?string $title = 'كشف حساب عميل';

    protected static string|\UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.customer-statement';

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
        return $schema
            ->statePath('data')
            ->components([
                Section::make('عوامل التصفية')
                    ->description('اختر العميل والفترة، ثم اضغط عرض الكشف.')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                            Select::make('customer_id')
                                ->label('العميل')
                                ->options(fn (): array => Customer::query()
                                    ->where('active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Customer $customer): array => [
                                        $customer->getKey() => trim("{$customer->name} — {$customer->code}", ' —'),
                                    ])->all())
                                ->searchable()
                                ->required()
                                ->native(false),
                            DatePicker::make('from_date')
                                ->label('من تاريخ')
                                ->native(false)
                                ->locale('ar')
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d')
                                ->closeOnDateSelection()
                                ->firstDayOfWeek(6),
                            DatePicker::make('to_date')
                                ->label('إلى تاريخ')
                                ->native(false)
                                ->locale('ar')
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d')
                                ->closeOnDateSelection()
                                ->firstDayOfWeek(6)
                                ->afterOrEqual('from_date'),
                            Select::make('transaction_type')
                                ->label('نوع الحركة')
                                ->options(fn (): array => app(CustomerStatementService::class)->transactionTypeOptions())
                                ->placeholder('كل الأنواع')
                                ->native(false),
                        ]),
                    ]),
            ]);
    }

    public function runReport(): void
    {
        $state = $this->form->getState();
        $this->report = app(CustomerStatementService::class)->report(
            (int) $state['customer_id'],
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
        if (! $this->hasRun || blank($this->data['customer_id'] ?? null)) {
            return null;
        }

        return route('customer-statement.print', array_filter([
            'customer' => $this->data['customer_id'],
            'from_date' => $this->data['from_date'] ?? null,
            'to_date' => $this->data['to_date'] ?? null,
            'transaction_type' => $this->data['transaction_type'] ?? null,
        ], fn ($value): bool => filled($value)));
    }

    public function excelUrl(): ?string
    {
        if (! $this->hasRun || blank($this->data['customer_id'] ?? null)) {
            return null;
        }

        return route('customer-statement.excel', array_filter([
            'customer' => $this->data['customer_id'],
            'from_date' => $this->data['from_date'] ?? null,
            'to_date' => $this->data['to_date'] ?? null,
            'transaction_type' => $this->data['transaction_type'] ?? null,
        ], fn ($value): bool => filled($value)));
    }
}
