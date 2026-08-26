<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Services\FiscalYearClosingService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class FiscalYearClosing extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationLabel = 'إقفال السنة المالية';
    protected static ?string $title = 'إقفال السنة المالية';
    protected static string|\UnitEnum|null $navigationGroup = 'الحسابات العامة';
    protected static ?int $navigationSort = 45;
    protected string $view = 'filament.pages.fiscal-year-closing';

    public array $yearData = [];
    public array $closingData = [];
    public ?array $preview = null;

    public function mount(): void
    {
        $this->yearForm->fill([
            'name' => (string) now()->year,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
        ]);

        $this->closingForm->fill([
            'fiscal_year_id' => FiscalYear::query()->latest('start_date')->value('id'),
        ]);
    }

    public function yearForm(Schema $schema): Schema
    {
        return $schema->statePath('yearData')->components([
            Section::make('إنشاء سنة مالية')->compact()->schema([
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    TextInput::make('name')->label('اسم السنة')->required()->maxLength(100),
                    DatePicker::make('start_date')->label('من تاريخ')->native(false)->required(),
                    DatePicker::make('end_date')->label('إلى تاريخ')->native(false)->required()->afterOrEqual('start_date'),
                ]),
            ]),
        ]);
    }

    public function closingForm(Schema $schema): Schema
    {
        return $schema->statePath('closingData')->components([
            Section::make('معاينة وإقفال السنة')->compact()->schema([
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    Select::make('fiscal_year_id')
                        ->label('السنة المالية')
                        ->options(fn (): array => FiscalYear::query()
                            ->orderByDesc('start_date')
                            ->get()
                            ->mapWithKeys(fn (FiscalYear $y): array => [
                                $y->getKey() => $y->name.' — '.($y->isClosed() ? 'مقفلة' : 'مفتوحة'),
                            ])->all())
                        ->searchable()->preload()->required(),
                    Select::make('retained_earnings_account_id')
                        ->label('حساب ترحيل نتيجة العام')
                        ->options(fn (): array => Account::query()
                            ->posting()
                            ->where('account_type', Account::TYPE_EQUITY)
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn (Account $a): array => [$a->getKey() => $a->displayName()])
                            ->all())
                        ->searchable()->preload()->required(),
                ]),
            ]),
        ]);
    }

    public function createYear(): void
    {
        $state = $this->yearForm->getState();

        $overlap = FiscalYear::query()
            ->whereDate('start_date', '<=', $state['end_date'])
            ->whereDate('end_date', '>=', $state['start_date'])
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'yearData.start_date' => 'توجد سنة مالية أخرى متداخلة مع هذه الفترة.',
            ]);
        }

        $year = FiscalYear::query()->create([
            ...$state,
            'status' => FiscalYear::STATUS_OPEN,
        ]);

        $this->closingForm->fill(['fiscal_year_id' => $year->getKey()]);
        $this->preview = null;

        Notification::make()->title('تم إنشاء السنة المالية')->success()->send();
    }

    public function previewClosing(): void
    {
        $state = $this->closingForm->getState();
        $year = FiscalYear::query()->findOrFail($state['fiscal_year_id']);

        $this->preview = app(FiscalYearClosingService::class)->preview(
            $year,
            (int) $state['retained_earnings_account_id'],
        );
    }

    public function closeYear(): void
    {
        $state = $this->closingForm->getState();
        $year = FiscalYear::query()->findOrFail($state['fiscal_year_id']);

        app(FiscalYearClosingService::class)->close(
            $year,
            (int) $state['retained_earnings_account_id'],
        );

        $this->preview = null;
        Notification::make()->title('تم إقفال السنة المالية بنجاح')->success()->send();
    }

    public function reopenYear(): void
    {
        $state = $this->closingForm->getState();
        $year = FiscalYear::query()->findOrFail($state['fiscal_year_id']);

        app(FiscalYearClosingService::class)->reopen($year);
        $this->preview = null;

        Notification::make()->title('تم إلغاء إقفال السنة المالية')->warning()->send();
    }

    public function selectedYear(): ?FiscalYear
    {
        $id = $this->closingData['fiscal_year_id'] ?? null;
        return $id ? FiscalYear::query()->find($id) : null;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && (
            (bool) ($user->is_admin ?? false)
            || $user->hasPermission('journal_entries.view')
        );
    }
}
