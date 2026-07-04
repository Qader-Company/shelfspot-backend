<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tasks:fail-expired')->dailyAt('00:00')->withoutOverlapping();
Schedule::command('tasks:release-expired-started --limit=100')->everyMinute()->withoutOverlapping();
<<<<<<< codex/analyze-expiration-and-scheduling-issue
Schedule::command('tasks:mark-overdue-in-progress --limit=100')->everyFiveMinutes()->withoutOverlapping();
=======
>>>>>>> main
Schedule::command('tasks:auto-accept-expired-review')->everyMinute();
