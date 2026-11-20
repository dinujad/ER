<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('cheque:send-due-reminders')->dailyAt('08:00')->withoutOverlapping(60);
Schedule::command('accounting:process-recurring')->dailyAt('01:15')->withoutOverlapping(60);
Schedule::command('accounting:send-payment-reminders')->dailyAt('09:30')->withoutOverlapping(60);
Schedule::command('accounting:process-scheduled-reports')->dailyAt('01:30')->withoutOverlapping(60);
Schedule::command('accounting:process-anomalies')->dailyAt('02:00')->withoutOverlapping(60);
Schedule::command('accounting:process-automation')->dailyAt('02:15')->withoutOverlapping(60);
