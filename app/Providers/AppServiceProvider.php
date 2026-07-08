<?php

namespace App\Providers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
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
        Event::listen(PasswordReset::class, function (PasswordReset $event) {
            $user = $event->user;

            // Use isset() or check the model schema directly
            if (isset($user->status)) {
                $user->update(['status' => true]);
            }
        });
    }
}
