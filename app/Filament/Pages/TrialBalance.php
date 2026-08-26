<?php

namespace App\Filament\Pages;

use App\Services\TrialBalanceService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrialBalance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationLabel = 'ميزان المراجعة';
    protected static ?string $title = 'ميزان المراجعة';
    protected static string|\UnitEnum|null $navigationGroup = 'الحسابات العامة';
    protected static ?int $navigationSort = 20;
    protected string $view = 'filament.pages.trial-balance';

    public array $data = [];
    public ?array $report = null;

    public function mount(): void
    {
        $this->form->fill(['to_date' => now()->toDateString(), 'movements_only' => true]);
        $this->runReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('الفترة والتصفية')->compact()->schema([
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    DatePicker::make('from_date')->label('من تاريخ')->native(false)->displayFormat('d/m/Y')->format('Y-m-d'),
                    DatePicker::make('to_date')->label('إلى تاريخ')->native(false)->displayFormat('d/m/Y')->format('Y-m-d')->afterOrEqual('from_date'),
                    Toggle::make('movements_only')->label('عرض الحسابات ذات الحركة فقط')->default(true),
                ]),
            ]),
        ]);
    }

    public function runReport(): void
    {
        $state = $this->form->getState();
        $this->report = app(TrialBalanceService::class)->report(
            $state['from_date'] ?? null, $state['to_date'] ?? null, (bool) ($state['movements_only'] ?? true),
        );
    }

    public function printUrl(): string
    {
        return route('trial-balance.print', array_filter([
            'from_date' => $this->data['from_date'] ?? null,
            'to_date' => $this->data['to_date'] ?? null,
            'movements_only' => ($this->data['movements_only'] ?? true) ? 1 : 0,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermission('trial_balance.view') === true;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('trial_balance.view') === true;
    }
}
