<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Octram ERP')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationItems([
                NavigationItem::make('إدارة المخزون')
                    ->group('المخازن')
                    ->icon('heroicon-o-cube')
                    ->extraAttributes(['class' => 'nav-icon-inventory-management'])
                    ->sort(10),
                NavigationItem::make('عمليات المخزون')
                    ->group('المخازن')
                    ->icon('heroicon-o-arrows-right-left')
                    ->extraAttributes(['class' => 'nav-icon-inventory-operations'])
                    ->sort(20),
                NavigationItem::make('تقارير المخزون')
                    ->group('التقارير')
                    ->icon('heroicon-o-chart-bar-square')
                    ->extraAttributes(['class' => 'nav-icon-inventory-reports'])
                    ->sort(10),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.styles.quantity-inputs'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.styles.purchase-request-balances'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.styles.sales-quotation-entries'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.styles.navigation-icons'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.styles.report-tables'),
            );
    }
}
