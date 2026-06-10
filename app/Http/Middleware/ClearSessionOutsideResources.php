<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class ClearSessionOutsideResources
{
    public function handle(Request $request, Closure $next): Response
    {
        $currentRoute = Route::currentRouteName();
        $allowedPatterns = [
            'filament.admin.resources.earnings.index',
            'filament.admin.resources.earnings.create',
            'filament.admin.resources.earnings.edit',
            'filament.admin.resources.payrolls.index',
            'filament.admin.resources.payrolls.create',
            'filament.admin.resources.payrolls.edit',
            'filament.admin.resources.date-periods.index',
            'filament.admin.resources.date-periods.create',
            'filament.admin.resources.date-periods.edit',
            'filament.admin.resources.atlogs.index',
            'filament.admin.resources.atlogs.create',
            'filament.admin.resources.atlogs.edit',
        ];

        $isAllowed = false;
        foreach ($allowedPatterns as $pattern) {
            if (str($currentRoute)->is($pattern)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            session()->forget([
                'earnings_employeeid', // Added to clean up your earnings session too
            ]);
        }

        if (!in_array($currentRoute, $allowedPatterns)) {
            session()->forget([
                'session_employeestatus',
                'session_employeetype',
                'session_employee_id',
                'session_periodcode', // Just in case you have this one floating around too
            ]);
        }

        return $next($request);
    }
}
