<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('calendar:dispatch-reminders')
    ->everyMinute();

Schedule::command('tasks:reject-expired')
    ->everyMinute();
