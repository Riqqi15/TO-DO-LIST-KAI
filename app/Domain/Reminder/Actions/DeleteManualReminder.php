<?php

namespace App\Domain\Reminder\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Reminder\Enums\ReminderKind;
use App\Domain\Reminder\Models\TodoReminder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class DeleteManualReminder
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(TodoReminder $reminder, User $actor): void
    {
        $todo = $reminder->todo;
        if (! $actor->can('update', $todo)) {
            throw new AuthorizationException;
        }
        if ($reminder->kind !== ReminderKind::Manual) {
            throw ValidationException::withMessages(['reminder' => 'Reminder otomatis tidak dapat dihapus manual.']);
        }
        $this->activity->handle($todo->workspace, $actor, 'todo.reminder_deleted', $reminder, ['scheduled_at' => $reminder->scheduled_at->toIso8601String()]);
        $reminder->delete();
    }
}
