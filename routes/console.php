<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tasks:fail-expired --limit=500')
    ->dailyAt('00:00')
    ->withoutOverlapping();
Schedule::command('tasks:fail-expired-reopened --limit=500')
    ->everyFiveMinutes()
    ->withoutOverlapping();
Schedule::command('tasks:release-expired-started --limit=500')
    ->everyFiveMinutes()
    ->withoutOverlapping();
Schedule::command('tasks:mark-overdue-in-progress --limit=500')
    ->everyFiveMinutes()
    ->withoutOverlapping();
Schedule::command('tasks:auto-accept-expired-review --limit=500')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
Schedule::command('notifications:health')
    ->everyMinute()
    ->withoutOverlapping();
