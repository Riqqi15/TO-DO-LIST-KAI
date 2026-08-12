<?php

namespace App\Domain\Todo\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Category\Models\Category;
use App\Domain\Reminder\Actions\CreateManualReminder;
use App\Domain\Reminder\Actions\SyncAutomaticReminders;
use App\Domain\Reminder\Enums\ReminderStatus;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\Todo\Models\Todo;
use App\Domain\Todo\Support\TodoDeadline;
use App\Models\User;
use App\Support\Wib;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTodo
{
    use AuthorizesDomainAction;

    public function __construct(private SyncAutomaticReminders $automatic, private CreateManualReminder $manual, private RecordActivity $activity) {}

    public function handle(Todo $todo, User $actor, Category $category, array $data, array $manualReminderTimes = []): Todo
    {
        $this->authorizeAbility($actor, 'update', $todo);
        if (! $category->is_system && $category->workspace_id !== $todo->workspace_id) {
            throw ValidationException::withMessages(['category_id' => 'Kategori tidak tersedia pada workspace ini.']);
        }
        $deadline = Wib::toUtc($data['deadline_at']);
        $deadlineChanged = ! $todo->deadline_at->equalTo($deadline);

        if ($deadlineChanged) {
            TodoDeadline::assertLeadTime($deadline);
        }

        return DB::transaction(function () use ($todo, $actor, $category, $data, $deadline, $manualReminderTimes, $deadlineChanged) {
            $old = $todo->only(['title', 'description', 'category_id', 'deadline_at']);
            $todo->update(['category_id' => $category->id, 'title' => $data['title'], 'description' => $data['description'] ?? null, 'deadline_at' => $deadline]);
            
            if ($deadlineChanged) {
                $todo->cancelManualRemindersFrom($deadline);
            }
            
            $automaticCount = $this->automatic->handle($todo);
            foreach ($manualReminderTimes as $time) {
                $this->manual->handle($todo, $actor, Wib::toUtc($time));
            }
            
            if ($deadlineChanged) {
                $manualCount = $todo->reminders()->manual()->where('status', ReminderStatus::Scheduled->value)->where('scheduled_at', '>', now())->count();
                if ($automaticCount === 0 && $manualCount === 0) {
                    throw ValidationException::withMessages(['manual_reminders' => 'Deadline ini memerlukan minimal satu reminder manual.']);
                }
            }
            $this->activity->handle($todo->workspace, $actor, 'todo.updated', $todo, null, ['old' => $old, 'new' => $todo->only(['title', 'description', 'category_id', 'deadline_at'])]);

            return $todo->load(['category', 'reminders']);
        });
    }
}
