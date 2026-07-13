<?php

namespace App\Console\Commands;

use App\Services\Calendar\CalendarReminderDispatcher;
use Illuminate\Console\Command;

class DispatchCalendarRemindersCommand extends Command
{
    protected $signature = 'calendar:dispatch-reminders';
    protected $description = 'Dispatch due calendar reminders';

    public function __construct(
        private readonly CalendarReminderDispatcher $dispatcher
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->dispatcher->dispatchDue();

        return self::SUCCESS;
    }
}