<?php

use App\Jobs\ProcessDueReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ProcessDueReminders)
    ->name('process-due-todo-reminders')
    ->everyMinute()
    ->withoutOverlapping();
