<?php

namespace App\Domain\Todo\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Reminder\Actions\SyncAutomaticReminders;
use App\Domain\Reminder\Enums\ReminderStatus;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\Todo\Enums\TodoStatus;
use App\Domain\Todo\Models\Todo;
use App\Domain\Todo\Support\TodoDeadline;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeTodoStatus
{
    use AuthorizesDomainAction;

    public function __construct(private SyncAutomaticReminders $automatic, private RecordActivity $activity) {}

    public function handle(Todo $todo, User $actor, TodoStatus $status, Carbon $statusAt, ?string $resultNotes = null): Todo
    {
        $this->authorizeAbility($actor, 'update', $todo);

        if ($status === TodoStatus::BelumDikerjakan) {
            TodoDeadline::assertLeadTime($statusAt, 'status_at');
        }
        if ($status !== TodoStatus::BelumDikerjakan && $todo->deadline_at && $statusAt->gt($todo->deadline_at)) {
            throw ValidationException::withMessages(['status_at' => 'Tanggal status tidak boleh melebihi deadline.']);
        }
        if ($status === TodoStatus::Selesai && $todo->started_at && $statusAt->lt($todo->started_at)) {
            throw ValidationException::withMessages(['status_at' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.']);
        }

        return DB::transaction(function () use ($todo, $actor, $status, $statusAt, $resultNotes) {
            $oldStatus = $todo->status;
            $old = $this->dateSnapshot($todo);
            $old['result_notes'] = $todo->result_notes;

            if ($status === TodoStatus::BelumDikerjakan) {
                $todo->update([
                    'status' => $status,
                    'deadline_at' => $statusAt,
                    'started_at' => null,
                    'completed_at' => null,
                    'result_notes' => null,
                ]);
                $todo->cancelManualRemindersFrom($statusAt);
                $this->automatic->handle($todo);
                $this->reactivateValidManualReminders($todo);
            } elseif ($status === TodoStatus::SedangDikerjakan) {
                $todo->update(['status' => $status, 'started_at' => $statusAt, 'completed_at' => null, 'result_notes' => null]);
                if ($oldStatus === TodoStatus::Selesai) {
                    $this->automatic->handle($todo);
                    $this->reactivateValidManualReminders($todo);
                }
            } else {
                $todo->update(['status' => $status, 'completed_at' => $statusAt, 'result_notes' => $resultNotes]);
            }

            if ($status === TodoStatus::Selesai) {
                $todo->reminders()->whereIn('status', [ReminderStatus::Scheduled->value, ReminderStatus::Failed->value])->update(['status' => ReminderStatus::Cancelled->value, 'cancelled_at' => now()]);
            }

            $todo->refresh();
            $this->activity->handle($todo->workspace, $actor, 'todo.status_changed', $todo, null, [
                'old' => ['status' => $oldStatus->value, ...$old],
                'new' => ['status' => $status->value, ...$this->dateSnapshot($todo)],
            ]);

            return $todo->load('reminders');
        });
    }

    private function reactivateValidManualReminders(Todo $todo): void
    {
        $todo->reminders()
            ->manual()
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<', $todo->deadline_at)
            ->update(['status' => ReminderStatus::Scheduled->value, 'cancelled_at' => null]);
    }

    private function dateSnapshot(Todo $todo): array
    {
        return [
            'deadline_at' => $todo->deadline_at?->toIso8601String(),
            'started_at' => $todo->started_at?->toIso8601String(),
            'completed_at' => $todo->completed_at?->toIso8601String(),
            'result_notes' => $todo->result_notes,
        ];
    }
}
