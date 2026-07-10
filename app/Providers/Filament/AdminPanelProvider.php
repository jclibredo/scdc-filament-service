<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Http\Middleware\ClearSessionOutsideResources;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->passwordReset()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandLogo(asset('images/scdc.jpg'))
            ->brandLogoHeight('3.5rem')
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
            ->renderHook(
                'panels::head.end',
                fn(): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString('
                <style>
                    /* 1. Shrink the login card container width */
                    .fi-simple-main-ctn {
                        max-width: 22rem !important;
                    }

                    /* 2. Add a border and roundness to the login card section */
                    .fi-simple-main-ctn > :first-child {
                        border: 2px solid #112a6e !important; /* Amber border */
                        box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.1), 0 2px 4px -2px rgba(245, 158, 11, 0.1) !important; /* Subtle amber glow shadow */
                    }

                    /* 3. Set a smaller global base font size for all text inside the login wrapper */
                    .fi-simple-layout-ctn {
                        font-size: 0.85rem !important;
                    }

                    /* 4. Scale down the primary form title (e.g., "Sign in") */
                    .fi-simple-header-heading {
                        font-size: 1.25rem !important;
                    }

                    /* 5. Target input elements, placeholders, and interactive buttons */
                    .fi-input-wrp input,
                    .fi-btn,
                    .fi-link,
                    .fi-fo-field-wrp-label label {
                        font-size: 0.825rem !important;
                    }

                    /* 6. Slightly adjust input padding so the smaller text feels balanced */
                    .fi-input-wrp input {
                        padding-top: 0.35rem !important;
                        padding-bottom: 0.35rem !important;
                    }
                </style>
            ')
            )
            ->maxContentWidth('full')
            ->authMiddleware([
                Authenticate::class,
            ]);
        // ->canAccess(function () {
        //     $user = auth()->user();
        //     return $user && $user->role === 'admin';
        // });
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
