<?php

use App\Services\ReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('appointments:send-reminders', function (ReminderService $reminderService): int {
    $sent = $reminderService->dispatch24HourReminders();
    $this->info("Sent {$sent} reminder(s).");

    return self::SUCCESS;
})->purpose('Send 24-hour appointment reminders by email/SMS');

Schedule::command('appointments:send-reminders')->everyMinute();
