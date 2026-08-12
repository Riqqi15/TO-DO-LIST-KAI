<?php

namespace App\Domain\Reminder\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Reminder\Enums\ReminderKind;
use App\Domain\Reminder\Enums\ReminderStatus;
use App\Domain\Reminder\Models\TodoReminder;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\Todo\Enums\TodoStatus;
use App\Domain\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateManualReminder
{
    use AuthorizesDomainAction;

    public function __construct(private RecordActivity $activity) {}

    public function handle(Todo $todo, User $actor, Carbon $scheduledAt): TodoReminder
    {
        $this->authorizeAbility($actor, 'update', $todo);
        if ($todo->status === TodoStatus::Selesai) {
            throw ValidationException::withMessages(['scheduled_at' => 'Task selesai tidak dapat memiliki reminder aktif.']);
        }
        if (! $scheduledAt->isFuture() || ! $scheduledAt->lt($todo->deadline_at)) {
            throw ValidationException::withMessages(['scheduled_at' => 'Reminder harus setelah waktu sekarang dan sebelum deadline.']);
        }
        $reminder = $todo->reminders()->create(['kind' => ReminderKind::Manual, 'scheduled_at' => $scheduledAt, 'status' => ReminderStatus::Scheduled]);
        $this->activity->handle($todo->workspace, $actor, 'todo.reminder_created', $reminder, ['scheduled_at' => $scheduledAt->toIso8601String()]);

        return $reminder;
    }
}
