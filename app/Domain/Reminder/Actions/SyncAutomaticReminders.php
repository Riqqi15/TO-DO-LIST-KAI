<?php

namespace App\Domain\Reminder\Actions;

use App\Domain\Reminder\Enums\ReminderKind;
use App\Domain\Reminder\Enums\ReminderStatus;
use App\Domain\Todo\Enums\TodoStatus;
use App\Domain\Todo\Models\Todo;
use Illuminate\Support\Carbon;

class SyncAutomaticReminders
{
    public function handle(Todo $todo): int
    {
        $todo->reminders()->whereIn('kind', [ReminderKind::AutomaticSevenDays->value, ReminderKind::AutomaticThreeDays->value])->delete();
        if ($todo->status === TodoStatus::Selesai) {
            return 0;
        }

        $count = 0;
        foreach ([[ReminderKind::AutomaticSevenDays, 7], [ReminderKind::AutomaticThreeDays, 3]] as [$kind, $days]) {
            $scheduled = Carbon::instance($todo->deadline_at)->subDays($days);
            if ($scheduled->isFuture()) {
                $todo->reminders()->create(['kind' => $kind, 'scheduled_at' => $scheduled, 'status' => ReminderStatus::Scheduled]);
                $count++;
            }
        }

        return $count;
    }
}
