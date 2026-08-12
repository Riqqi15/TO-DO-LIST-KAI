<?php

namespace App\Domain\Reminder\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Reminder\Enums\ReminderKind;
use App\Domain\Reminder\Models\TodoReminder;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DeleteManualReminder
{
    use AuthorizesDomainAction;

    public function __construct(private RecordActivity $activity) {}

    public function handle(TodoReminder $reminder, User $actor): void
    {
        $todo = $reminder->todo;
        $this->authorizeAbility($actor, 'update', $todo);
        if ($reminder->kind !== ReminderKind::Manual) {
            throw ValidationException::withMessages(['reminder' => 'Reminder otomatis tidak dapat dihapus manual.']);
        }
        $this->activity->handle($todo->workspace, $actor, 'todo.reminder_deleted', $reminder, ['scheduled_at' => $reminder->scheduled_at->toIso8601String()]);
        $reminder->delete();
    }
}
