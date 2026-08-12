<?php

namespace App\Domain\Todo\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class TodoDeadline
{
    public const MIN_LEAD_MINUTES = 5;

    /**
     * Guard that a deadline is far enough in the future.
     */
    public static function assertLeadTime(DateTimeInterface $deadline, string $field = 'deadline_at'): void
    {
        if (Carbon::instance($deadline)->lt(now()->addMinutes(self::MIN_LEAD_MINUTES))) {
            throw ValidationException::withMessages([
                $field => 'Deadline minimal '.self::MIN_LEAD_MINUTES.' menit dari sekarang.',
            ]);
        }
    }
}
