<?php

use Illuminate\Support\Facades\Schedule;

/*
| All schedules run in the business timezone. Digests are generated as DRAFTS for editors.
*/
$timezone = (string) config('app.business_timezone');

Schedule::command('digests:generate news daily')->dailyAt('06:00')->timezone($timezone)->withoutOverlapping();
Schedule::command('digests:generate events weekly')->weeklyOn(0, '18:00')->timezone($timezone)->withoutOverlapping();
Schedule::command('digests:generate news monthly')->monthlyOn(1, '06:00')->timezone($timezone)->withoutOverlapping();
Schedule::command('digests:generate events monthly')->monthlyOn(1, '06:05')->timezone($timezone)->withoutOverlapping();

Schedule::command('content:publish-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('reminders:events')->hourly()->withoutOverlapping();
Schedule::command('maintenance:prune')->dailyAt('03:15')->timezone($timezone);
