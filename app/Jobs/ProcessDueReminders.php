<?php

namespace App\Jobs;

use App\Domain\Reminder\Actions\SendDueReminders;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDueReminders implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function handle(SendDueReminders $action): void
    {
        $action->handle();
    }
}
