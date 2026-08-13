<?php

namespace App\Domain\Todo\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Category\Models\Category;
use App\Domain\Reminder\Actions\CreateManualReminder;
use App\Domain\Reminder\Actions\SyncAutomaticReminders;
use App\Domain\Reminder\Enums\ReminderKind;
use App\Domain\Reminder\Enums\ReminderStatus;
use App\Domain\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTodo
{
    public function __construct(private SyncAutomaticReminders $automatic, private CreateManualReminder $manual, private RecordActivity $activity) {}

    public function handle(Todo $todo, User $actor, Category $category, array $data, array $manualReminderTimes = []): Todo
    {
        if (! $actor->can('update', $todo)) {
            throw new AuthorizationException;
        }
        if (! $category->is_system && $category->workspace_id !== $todo->workspace_id) {
            throw ValidationException::withMessages(['category_id' => 'Kategori tidak tersedia pada workspace ini.']);
        }
        $deadline = Carbon::parse($data['deadline_at'], 'Asia/Jakarta')->utc();
        $deadlineChanged = ! $todo->deadline_at->equalTo($deadline);
        // Validasi ini di-nonaktifkan sementara untuk keperluan testing.
        // Aktifkan kembali jika ingin membatasi perubahan deadline minimal 5 menit dari sekarang.
        // if ($deadlineChanged && $deadline->lt(now()->addMinutes(5))) {
        //     throw ValidationException::withMessages(['deadline_at' => 'Deadline minimal 5 menit dari sekarang.']);
        // }

        return DB::transaction(function () use ($todo, $actor, $category, $data, $deadline, $manualReminderTimes, $deadlineChanged) {
            $old = $todo->only(['title', 'description', 'category_id', 'start_date', 'deadline_at']);
            $todo->update(['category_id' => $category->id, 'title' => $data['title'], 'description' => $data['description'] ?? null, 'start_date' => $data['start_date'] ?? null, 'deadline_at' => $deadline]);
            
            if ($deadlineChanged) {
                $todo->reminders()->where('kind', ReminderKind::Manual->value)->where('scheduled_at', '>=', $deadline)->update(['status' => ReminderStatus::Cancelled->value, 'cancelled_at' => now()]);
            }
            
            $automaticCount = $this->automatic->handle($todo);
            foreach ($manualReminderTimes as $time) {
                $this->manual->handle($todo, $actor, Carbon::parse($time, 'Asia/Jakarta')->utc());
            }
            
            if ($deadlineChanged) {
                $manualCount = $todo->reminders()->where('kind', ReminderKind::Manual->value)->where('status', ReminderStatus::Scheduled->value)->where('scheduled_at', '>', now())->count();
                if ($automaticCount === 0 && $manualCount === 0) {
                    throw ValidationException::withMessages(['manual_reminders' => 'Deadline ini memerlukan minimal satu reminder manual.']);
                }
            }
            $this->activity->handle($todo->workspace, $actor, 'todo.updated', $todo, null, ['old' => $old, 'new' => $todo->only(['title', 'description', 'category_id', 'start_date', 'deadline_at'])]);

            return $todo->load(['category', 'reminders']);
        });
    }
}
