<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ClearSessionOutsideResources;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
            // ->renderHook(
            //     'panels::head.end',
            //     fn(): HtmlString => new HtmlString('
            //         <style>
            //             /* ==========================================================================
            //                SIDEBAR NAVIGATION AMBER STYLES
            //                ========================================================================== */
            //             /* 1. Base / Inactive Sidebar Icon Color */
            //             .fi-sidebar-item-icon {
            //                 color: #94a3b8 !important;
            //             }

            //             /* 2. Hover Sidebar Icon Color */
            //             .fi-sidebar-item:hover .fi-sidebar-item-icon {
            //                 color: #64748b !important;
            //             }

            //             /* 3. Active / Current Tab Icon Color */
            //             .fi-sidebar-item-active .fi-sidebar-item-icon {
            //                 color: #f59e0b !important;
            //             }

            //             /* ==========================================================================
            //                HEADER TABS / TOP NAVIGATION AMBER STYLES
            //                ========================================================================== */
            //             /* 4. Active Header Tab Text Color */
            //             .fi-tabs-item-active {
            //                 color: #f59e0b !important;
            //                 background-color: #fffbeb !important; /* Soft Amber background fill */
            //                 border-radius: 0.375rem;
            //             }

            //             /* 5. FORCE ACTIVE HEADER TAB ICON COLOR (Targets the internal SVG) */
            //             .fi-tabs-item-active .fi-tabs-item-icon,
            //             .fi-tabs-item-active svg {
            //                 color: #f59e0b !important;
            //                 fill: currentColor !important; /* Ensures path vectors inherit the color change */
            //             }

            //             /* 6. Active Header Tab Bottom Border / Indicator Line */
            //             .fi-tabs-item-active::after {
            //                 background-color: #f59e0b !important;
            //             }
            //         </style>
            //     ')
            // )
            ->maxContentWidth('full')
            ->authMiddleware([
                Authenticate::class,
            ]);
        // ->canAccess(function () {
        //     $user = auth()->user();
        //     return $user && $user->role === 'admin';
        // });
    }
}
