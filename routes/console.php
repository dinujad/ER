<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('cheque:send-due-reminders')->dailyAt('08:00');
Schedule::command('accounting:process-recurring')->dailyAt('01:15');
Schedule::command('accounting:process-scheduled-reports')->dailyAt('01:30');
Schedule::command('accounting:process-anomalies')->dailyAt('02:00');
Schedule::command('accounting:process-automation')->dailyAt('02:15');
