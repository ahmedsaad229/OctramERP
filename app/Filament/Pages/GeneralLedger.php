<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Services\GeneralLedgerService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Route;

class GeneralLedger extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'الأستاذ العام';

    protected static ?string $title = 'الأستاذ العام';

    protected static string|\UnitEnum|null $navigationGroup = 'الحسابات العامة';

    protected static ?int $navigationSort = 25;

    protected string $view = 'filament.pages.general-ledger';

    public array $data = [];

    public ?array $report = null;

    public function mount(): void
    {
        $firstAccountId = Account::query()
            ->posting()
            ->orderBy('code')
            ->value('id');

        $this->form->fill([
            'account_id' => $firstAccountId,
            'to_date' => now()->toDateString(),
        ]);

        if ($firstAccountId) {
            $this->runReport();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('اختيار الحساب والفترة')
                    ->compact()
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 3,
                        ])->schema([
                            Select::make('account_id')
                                ->label('الحساب')
                                ->options(fn (): array => Account::query()
                                    ->posting()
                                    ->orderBy('code')
                                    ->get()
                                    ->mapWithKeys(
                                        fn (Account $account): array => [
                                            $account->getKey() => $account->displayName(),
                                        ]
                                    )
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required(),

                            DatePicker::make('from_date')
                                ->label('من تاريخ')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d'),

                            DatePicker::make('to_date')
                                ->label('إلى تاريخ')
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
        $state = $this->form->getState();

        if (blank($state['account_id'] ?? null)) {
            Notification::make()
                ->title('اختر حسابًا أولًا')
                ->warning()
                ->send();

            $this->report = null;

            return;
        }

        $this->report = app(GeneralLedgerService::class)->report(
            (int) $state['account_id'],
            $state['from_date'] ?? null,
            $state['to_date'] ?? null,
        );
    }

    public function printUrl(): string
    {
        if (! Route::has('general-ledger.print')) {
            return '#';
        }

        return route('general-ledger.print', array_filter([
            'account_id' => $this->data['account_id'] ?? null,
            'from_date' => $this->data['from_date'] ?? null,
            'to_date' => $this->data['to_date'] ?? null,
        ], fn ($value): bool => $value !== null && $value !== ''));
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
            ? $user->hasPermission('general_ledger.view')
            : true;
    }
}