<?php

namespace App\Filament\Pages;

use App\Services\IncomeStatementService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Route;

class IncomeStatement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'قائمة الدخل';
    protected static ?string $title = 'قائمة الدخل';
    protected static string|\UnitEnum|null $navigationGroup = 'الحسابات العامة';
    protected static ?int $navigationSort = 30;
    protected string $view = 'filament.pages.income-statement';

    public array $data = [];
    public ?array $report = null;

    public function mount(): void
    {
        $this->form->fill([
            'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->toDateString(),
            'details' => true,
        ]);
        $this->runReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('الفترة وخيارات العرض')->compact()->schema([
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    DatePicker::make('from_date')->label('من تاريخ')->native(false)->displayFormat('d/m/Y')->format('Y-m-d'),
                    DatePicker::make('to_date')->label('إلى تاريخ')->native(false)->displayFormat('d/m/Y')->format('Y-m-d')->afterOrEqual('from_date'),
                    Toggle::make('details')->label('عرض تفاصيل الحسابات')->default(true),
                ]),
            ]),
        ]);
    }

    public function runReport(): void
    {
        $state = $this->form->getState();
        $this->report = app(IncomeStatementService::class)->report(
            $state['from_date'] ?? null,
            $state['to_date'] ?? null,
            (bool) ($state['details'] ?? true),
        );
    }

    public function printUrl(): string
    {
        if (! Route::has('income-statement.print')) {
            return '#';
        }

        return route('income-statement.print', array_filter([
            'from_date' => $this->data['from_date'] ?? null,
            'to_date' => $this->data['to_date'] ?? null,
            'details' => ($this->data['details'] ?? true) ? 1 : 0,
        ], fn ($value): bool => $value !== null && $value !== ''));
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ((bool) ($user->is_admin ?? false) || $user->hasPermission('income_statement.view'));
    }
}
