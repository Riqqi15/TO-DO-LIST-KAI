<?php

namespace App\Domain\Todo\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Category\Models\Category;
use App\Domain\Reminder\Actions\CreateManualReminder;
use App\Domain\Reminder\Actions\SyncAutomaticReminders;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\Todo\Enums\TodoStatus;
use App\Domain\Todo\Models\Todo;
use App\Domain\Todo\Support\TodoDeadline;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use App\Support\Wib;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTodo
{
    use AuthorizesDomainAction;

    public function __construct(private SyncAutomaticReminders $automatic, private CreateManualReminder $manual, private RecordActivity $activity) {}

    public function handle(Workspace $workspace, User $actor, Category $category, array $data, array $manualReminderTimes = []): Todo
    {
        $this->authorizeWorkspaceMember($workspace, $actor);
        if (! $category->is_system && $category->workspace_id !== $workspace->id) {
            throw ValidationException::withMessages(['category_id' => 'Kategori tidak tersedia pada workspace ini.']);
        }
        $deadline = Wib::toUtc($data['deadline_at']);
        TodoDeadline::assertLeadTime($deadline);

        return DB::transaction(function () use ($workspace, $actor, $category, $data, $deadline, $manualReminderTimes) {
            $todo = Todo::create(['workspace_id' => $workspace->id, 'created_by' => $actor->id, 'category_id' => $category->id, 'title' => $data['title'], 'description' => $data['description'] ?? null, 'status' => TodoStatus::BelumDikerjakan, 'deadline_at' => $deadline]);
            $automaticCount = $this->automatic->handle($todo);
            foreach ($manualReminderTimes as $time) {
                $this->manual->handle($todo, $actor, Wib::toUtc($time));
            }
            if ($automaticCount === 0 && count($manualReminderTimes) === 0) {
                throw ValidationException::withMessages(['manual_reminders' => 'Semua reminder otomatis sudah lewat. Buat minimal satu reminder manual.']);
            }
            $this->activity->handle($workspace, $actor, 'todo.created', $todo, $todo->only(['id', 'title', 'description', 'status', 'deadline_at', 'category_id']));

            return $todo->load(['category', 'reminders']);
        });
    }
}
