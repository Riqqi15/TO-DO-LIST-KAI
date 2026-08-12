<?php

namespace App\Http\Controllers\Reminder;

use App\Domain\Reminder\Actions\CreateManualReminder;
use App\Domain\Reminder\Actions\DeleteManualReminder;
use App\Domain\Reminder\Models\TodoReminder;
use App\Domain\Todo\Models\Todo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reminder\StoreManualReminderRequest;
use App\Support\Wib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function store(StoreManualReminderRequest $request, Todo $todo, CreateManualReminder $action): RedirectResponse
    {
        $scheduledAt = Wib::toUtc($request->validated('scheduled_at'));
        $action->handle($todo, $request->user(), $scheduledAt);

        return back()->with('success', 'Reminder manual dibuat.');
    }

    public function destroy(Request $request, TodoReminder $reminder, DeleteManualReminder $action): RedirectResponse
    {
        $action->handle($reminder, $request->user());

        return back()->with('success', 'Reminder manual dihapus.');
    }
}
