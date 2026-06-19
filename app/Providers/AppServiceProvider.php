<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registers your style asset globally to render in Filament's lifecycle hooks
        // FilamentAsset::register([
        //     Css::make('custom-sidebar-styles', public_path('css/custom-filament.css')),
        // ]);
    }
}
