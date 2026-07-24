<?php

namespace App\Providers\Filament;

// use App\Filament\Resources\Categories\CategoryResource;
// use App\Filament\Resources\Employees\EmployeeResource;
use App\Http\Middleware\ClearSessionOutsideResources;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
// use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
// use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s') // Poll every 30 seconds
            ->passwordReset()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandLogo(asset('images/scdc.jpg'))
            ->brandLogoHeight('5.5rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->bootUsing(function (Panel $panel) {
                // Register default fallback icon globally
                FilamentIcon::register([
                    'panels::resources.pages.index-page.navigation-item' => Heroicon::OutlinedCog,
                ]);
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                ClearSessionOutsideResources::class,
            ])
            ->breadcrumbs()
            ->darkmode(false)
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        Filament::serving(function () {
            $panel = Filament::getCurrentPanel();

            if ($panel) {
                $panel->navigationGroups([
                    NavigationGroup::make()
                        ->label('Archive Management')
                        ->icon('heroicon-o-archive-box'), // Box/Storage icon

                    NavigationGroup::make()
                        ->label('Report Management')
                        ->icon('heroicon-o-document-chart-bar'), // Chart/Analytics document

                    NavigationGroup::make()
                        ->label('Utility Management')
                        ->icon('heroicon-o-wrench-screwdriver'), // Tools/Settings icon

                    NavigationGroup::make()
                        ->label('User Management')
                        ->icon('heroicon-o-users'), // Group of users icon
                    NavigationGroup::make()
                        ->label('Activity')
                        ->icon('heroicon-o-shield-check'),
                ]);

                // Safely sort the items inside EVERY group alphabetically
                foreach ($panel->getNavigationGroups() as $group) {
                    if ($group instanceof NavigationGroup) {
                        $items = $group->getItems();

                        usort($items, function ($a, $b) {
                            return strcmp($a->getLabel(), $b->getLabel());
                        });

                        $group->items($items);
                    }
                }
            }
        });
    }
}
