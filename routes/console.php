<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('remi:send-due-reminders')
    ->everyMinute()
    ->withoutOverlapping(5);

\Illuminate\Support\Facades\Schedule::command('remi:send-morning-digest')
    ->everyFifteenMinutes()
    ->withoutOverlapping(10);

\Illuminate\Support\Facades\Schedule::command('remi:send-habit-checkin')
    ->hourly()
    ->withoutOverlapping(10);

\Illuminate\Support\Facades\Schedule::command('remi:backup --verify --keep=7')
    ->dailyAt('02:00')
    ->withoutOverlapping(15);


