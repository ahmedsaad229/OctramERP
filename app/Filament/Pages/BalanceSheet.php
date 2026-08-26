<?php

namespace App\Filament\Pages;

use App\Services\BalanceSheetService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Route;

class BalanceSheet extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationLabel = 'الميزانية العمومية';
    protected static ?string $title = 'الميزانية العمومية';
    protected static string|\UnitEnum|null $navigationGroup = 'الحسابات العامة';
    protected static ?int $navigationSort = 35;
    protected string $view = 'filament.pages.balance-sheet';

    public array $data = [];
    public ?array $report = null;

    public function mount(): void
    {
        $this->form->fill([
            'as_of_date' => now()->toDateString(),
            'details' => true,
        ]);

        $this->runReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('تاريخ وخيارات العرض')->compact()->schema([
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    DatePicker::make('as_of_date')
                        ->label('الميزانية في تاريخ')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->required(),
                    Toggle::make('details')
                        ->label('عرض تفاصيل الحسابات')
                        ->default(true),
                ]),
            ]),
        ]);
    }

    public function runReport(): void
    {
        $state = $this->form->getState();

        $this->report = app(BalanceSheetService::class)->report(
            $state['as_of_date'] ?? now()->toDateString(),
            (bool) ($state['details'] ?? true),
        );
    }

    public function printUrl(): string
    {
        if (! Route::has('balance-sheet.print')) {
            return '#';
        }

        return route('balance-sheet.print', array_filter([
            'as_of_date' => $this->data['as_of_date'] ?? null,
            'details' => ($this->data['details'] ?? true) ? 1 : 0,
        ], fn ($value): bool => $value !== null && $value !== ''));
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->hasPermission('balance_sheet.view');
    }
}
