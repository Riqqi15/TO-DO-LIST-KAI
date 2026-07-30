<?php

namespace App\Domain\Todo\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Reminder\Actions\CreateManualReminder;
use App\Domain\Reminder\Actions\SyncAutomaticReminders;
use App\Domain\Reminder\Enums\ReminderKind;
use App\Domain\Reminder\Enums\ReminderStatus;
use App\Domain\Todo\Enums\TodoStatus;
use App\Domain\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeTodoStatus
{
    public function __construct(private SyncAutomaticReminders $automatic, private CreateManualReminder $manual, private RecordActivity $activity) {}

    public function handle(Todo $todo, User $actor, TodoStatus $status, ?Carbon $manualReminderAt = null): Todo
    {
        if (! $actor->can('update', $todo)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($todo, $actor, $status, $manualReminderAt) {
            $old = $todo->status;
            $todo->update(['status' => $status]);
            if ($status === TodoStatus::Selesai) {
                $todo->reminders()->whereIn('status', [ReminderStatus::Scheduled->value, ReminderStatus::Failed->value])->update(['status' => ReminderStatus::Cancelled->value, 'cancelled_at' => now()]);
            } elseif ($old === TodoStatus::Selesai) {
                $this->automatic->handle($todo);
                $todo->reminders()->where('kind', ReminderKind::Manual->value)->where('scheduled_at', '>', now())->update(['status' => ReminderStatus::Scheduled->value, 'cancelled_at' => null]);
                if ($manualReminderAt) {
                    $this->manual->handle($todo, $actor, $manualReminderAt);
                }
                if (! $todo->reminders()->where('status', ReminderStatus::Scheduled->value)->where('scheduled_at', '>', now())->exists()) {
                    throw ValidationException::withMessages(['status' => 'Task dibuka kembali tetapi tidak memiliki reminder mendatang. Tambahkan reminder manual terlebih dahulu.']);
                }
            }
            $this->activity->handle($todo->workspace, $actor, 'todo.status_changed', $todo, null, ['status' => ['old' => $old->value, 'new' => $status->value]]);

            return $todo->load('reminders');
        });
    }
}
