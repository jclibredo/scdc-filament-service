<?php

// use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    Artisan::call('queue:work', [
        '--stop-when-empty' => true,
        '--tries' => 3,
    ]);
})
    ->name('process-queue-work') // <--- Add this line
    ->everyMinute()
    ->withoutOverlapping();
