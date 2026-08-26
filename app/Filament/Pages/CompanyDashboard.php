<?php

namespace App\Filament\Pages;

use App\Services\DashboardService;
use Filament\Pages\Page;

class CompanyDashboard extends Page
{
    protected static ?string $navigationLabel = 'الرئيسية';

    protected static ?string $title = 'لوحة المعلومات';

    protected static ?string $slug = 'dashboard';

    protected static ?int $navigationSort = -90;

    protected string $view = 'filament.pages.dashboard';

    public string $period = 'month';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public function updatedPeriod(string $period): void
    {
        if ($period !== 'custom') {
            return;
        }

        $this->fromDate ??= now()->startOfMonth()->toDateString();
        $this->toDate ??= now()->toDateString();
    }

    public static function canAccess(): bool
    {
        return static::hasDashboardPermission();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::hasDashboardPermission();
    }

    private static function hasDashboardPermission(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ((bool) ($user->is_admin ?? false)) {
            return true;
        }

        return $user->hasPermission('dashboard.view');
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'dashboard' => app(DashboardService::class)->data(
                $this->period,
                $this->fromDate,
                $this->toDate,
            ),
        ];
    }
}
