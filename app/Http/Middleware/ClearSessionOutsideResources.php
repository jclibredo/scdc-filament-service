<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class ClearSessionOutsideResources
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the current route name
        $currentRoute = Route::currentRouteName();

        // Use patterns to match all sub-routes of a resource
        // This covers .index, .create, .edit, .view, etc.
        $allowedPatterns = [
            'filament.admin.resources.earnings.index',
            'filament.admin.resources.earnings.create',
            'filament.admin.resources.earnings.edit',
        ];

        $isAllowed = false;
        foreach ($allowedPatterns as $pattern) {
            if (str($currentRoute)->is($pattern)) {
                $isAllowed = true;
                break;
            }
        }

        // If not on an allowed resource, purge the specific session keys
        if (!$isAllowed) {
            session()->forget([
                'earnings_employeeid', // Added to clean up your earnings session too
            ]);
        }

        return $next($request);
    }
}
