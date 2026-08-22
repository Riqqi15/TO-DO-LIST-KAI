<?php

namespace App\Domain\Todo\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Category\Models\Category;
use App\Domain\Reminder\Actions\CreateManualReminder;
use App\Domain\Reminder\Actions\SyncAutomaticReminders;
use App\Domain\Todo\Enums\TodoStatus;
use App\Domain\Todo\Models\Todo;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTodo
{
    public function __construct(private SyncAutomaticReminders $automatic, private CreateManualReminder $manual, private RecordActivity $activity) {}

    public function handle(Workspace $workspace, User $actor, Category $category, array $data, array $manualReminderTimes = []): Todo
    {
        if (! $workspace->hasMember($actor)) {
            throw new AuthorizationException;
        }
        if (! $category->is_system && $category->workspace_id !== $workspace->id) {
            throw ValidationException::withMessages(['category_id' => 'Kategori tidak tersedia pada workspace ini.']);
        }
        $deadline = Carbon::parse($data['deadline_at'], 'Asia/Jakarta')->utc();
        if ($deadline->lt(now()->addMinutes(5))) {
             throw ValidationException::withMessages(['deadline_at' => 'Deadline minimal 5 menit dari sekarang.']);
        }

        return DB::transaction(function () use ($workspace, $actor, $category, $data, $deadline, $manualReminderTimes) {
            $todo = Todo::create(['workspace_id' => $workspace->id, 'created_by' => $actor->id, 'category_id' => $category->id, 'title' => $data['title'], 'description' => $data['description'] ?? null, 'status' => TodoStatus::BelumDikerjakan, 'start_date' => $data['start_date'] ?? null, 'deadline_at' => $deadline]);
            $automaticCount = $this->automatic->handle($todo);
            foreach ($manualReminderTimes as $time) {
                $this->manual->handle($todo, $actor, Carbon::parse($time, 'Asia/Jakarta')->utc());
            }
            if ($automaticCount === 0 && count($manualReminderTimes) === 0) {
                 throw ValidationException::withMessages(['manual_reminders' => 'Semua reminder otomatis sudah lewat. Buat minimal satu reminder manual.']);
            }
            $this->activity->handle($workspace, $actor, 'todo.created', $todo, $todo->only(['id', 'title', 'description', 'status', 'start_date', 'deadline_at', 'category_id']));

            return $todo->load(['category', 'reminders']);
        });
    }
}
