<?php

namespace App\Domain\Reminder\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Reminder\Enums\DeliveryStatus;
use App\Domain\Reminder\Enums\ReminderStatus;
use App\Domain\Reminder\Models\TodoReminder;
use App\Notifications\Todo\TodoReminderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendDueReminders
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(): int
    {
        $ids = DB::transaction(function () {
            TodoReminder::query()
                ->where('status', ReminderStatus::Processing->value)
                ->where('updated_at', '<', now()->subMinutes(10))
                ->update(['status' => ReminderStatus::Scheduled->value]);

            $ids = TodoReminder::query()
                ->where(function ($query) {
                    $query->where('status', ReminderStatus::Scheduled->value)
                        ->orWhere(function ($failed) {
                            $failed->where('status', ReminderStatus::Failed->value)
                                ->whereHas('deliveries', fn ($delivery) => $delivery->where('attempts', '<', 3));
                        });
                })
                ->where('scheduled_at', '<=', now())
                ->lockForUpdate()
                ->pluck('id');
            TodoReminder::whereKey($ids)->update(['status' => ReminderStatus::Processing->value]);

            return $ids;
        });

        $processed = 0;
        foreach ($ids as $id) {
            $reminder = TodoReminder::with(['todo.workspace.members'])->find($id);
            if (! $reminder) {
                Log::warning('Reminder hilang sebelum diproses.', ['reminder_id' => $id]);

                continue;
            }

            try {
                $processed += $this->deliver($reminder);
            } catch (Throwable $exception) {
                report($exception);
                $reminder->update(['status' => ReminderStatus::Failed]);
            }
        }

        return $processed;
    }

    private function deliver(TodoReminder $reminder): int
    {
        $workspace = $reminder->todo?->workspace;
        if (! $workspace) {
            Log::warning('Reminder tidak memiliki todo atau workspace yang valid.', ['reminder_id' => $reminder->getKey()]);
            $reminder->update(['status' => ReminderStatus::Failed]);

            return 0;
        }

        $recipients = $workspace->members->filter(fn ($user) => $user->hasVerifiedEmail());
        if ($recipients->isEmpty()) {
            Log::warning('Reminder tidak dikirim karena tidak ada anggota dengan email terverifikasi.', [
                'reminder_id' => $reminder->getKey(),
                'workspace_id' => $workspace->getKey(),
            ]);
            $reminder->update(['status' => ReminderStatus::Failed]);

            return 0;
        }

        $hasFailure = false;
        foreach ($recipients as $recipient) {
            $delivery = $reminder->deliveries()->firstOrCreate(['user_id' => $recipient->id], ['status' => DeliveryStatus::Pending]);
            if ($delivery->status === DeliveryStatus::Sent) {
                continue;
            }
            try {
                $recipient->notify(new TodoReminderNotification($reminder->todo));
                $delivery->update(['status' => DeliveryStatus::Sent, 'attempts' => $delivery->attempts + 1, 'sent_at' => now(), 'failed_at' => null, 'last_error' => null]);
            } catch (Throwable $exception) {
                report($exception);
                $hasFailure = true;
                $delivery->update(['status' => DeliveryStatus::Failed, 'attempts' => $delivery->attempts + 1, 'failed_at' => now(), 'last_error' => mb_substr($exception->getMessage(), 0, 2000)]);
            }
        }

        $reminder->update(['status' => $hasFailure ? ReminderStatus::Failed : ReminderStatus::Sent]);
        $this->activity->handle($workspace, null, 'todo.reminder_dispatched', $reminder, ['recipient_count' => $recipients->count(), 'status' => $reminder->status->value]);

        return 1;
    }
}
