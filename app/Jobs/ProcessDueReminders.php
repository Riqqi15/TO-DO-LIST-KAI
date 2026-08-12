<?php

namespace App\Jobs;

use App\Domain\Reminder\Actions\SendDueReminders;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessDueReminders implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function handle(SendDueReminders $action): void
    {
        $action->handle();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Job pengiriman reminder gagal setelah semua percobaan.', [
            'exception' => $exception?->getMessage(),
        ]);
    }
}
