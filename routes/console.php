<?php

use App\Console\Commands\CheckWeeklyTimeLimits;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\SendDailyNotifications;
use App\Console\Commands\SendActivityDigests;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command(CheckWeeklyTimeLimits::class)->everyMinute();
Schedule::command(SendDailyNotifications::class)->dailyAt('22:00');
Schedule::command(SendActivityDigests::class)->dailyAt('22:00');
