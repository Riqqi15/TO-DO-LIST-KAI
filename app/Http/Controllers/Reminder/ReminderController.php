<?php

namespace App\Http\Controllers\Reminder;

use App\Domain\Reminder\Actions\CreateManualReminder;
use App\Domain\Reminder\Actions\DeleteManualReminder;
use App\Domain\Reminder\Models\TodoReminder;
use App\Domain\Todo\Models\Todo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reminder\StoreManualReminderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReminderController extends Controller
{
    public function store(StoreManualReminderRequest $request, Todo $todo, CreateManualReminder $action): RedirectResponse
    {
        $scheduledAt = Carbon::parse($request->validated('scheduled_at'), 'Asia/Jakarta')->utc();
        $action->handle($todo, $request->user(), $scheduledAt);

        return back()->with('success', 'Reminder manual dibuat.');
    }

    public function destroy(Request $request, TodoReminder $reminder, DeleteManualReminder $action): RedirectResponse
    {
        $action->handle($reminder, $request->user());

        return back()->with('success', 'Reminder manual dihapus.');
    }
}
