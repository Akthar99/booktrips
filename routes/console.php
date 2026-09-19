<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('booktrips:escalate-stale-bookings')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Mail, SMS and notification emails travel through the queue. This
// minute-by-minute safety net drains it even when the long-running Ploi
// worker daemon is down, so queued messages arrive within a minute at worst.
// A healthy daemon still delivers them within seconds.
Schedule::command('queue:work --stop-when-empty --max-time=50 --sleep=1 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(2);

Schedule::command('booktrips:sitemap:generate')
    ->dailyAt('03:30')
    ->withoutOverlapping();
