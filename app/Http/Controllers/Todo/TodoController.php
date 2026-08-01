<?php

namespace App\Http\Controllers\Todo;

use App\Domain\Category\Models\Category;
use App\Domain\Todo\Actions\ChangeTodoStatus;
use App\Domain\Todo\Actions\CreateTodo;
use App\Domain\Todo\Actions\DeleteTodo;
use App\Domain\Todo\Actions\UpdateTodo;
use App\Domain\Todo\Enums\TodoStatus;
use App\Domain\Todo\Models\Todo;
use App\Domain\Workspace\Models\Workspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Todo\ChangeTodoStatusRequest;
use App\Http\Requests\Todo\StoreTodoRequest;
use App\Http\Requests\Todo\UpdateTodoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TodoController extends Controller
{
    public function store(StoreTodoRequest $request, Workspace $workspace, CreateTodo $action): RedirectResponse
    {
        $data = $request->validated();
        $action->handle($workspace, $request->user(), Category::findOrFail($data['category_id']), $data, $data['manual_reminders'] ?? []);

        return back()->with('success', 'Task dibuat.');
    }

    public function update(UpdateTodoRequest $request, Todo $todo, UpdateTodo $action): RedirectResponse
    {
        $data = $request->validated();
        $action->handle($todo, $request->user(), Category::findOrFail($data['category_id']), $data, $data['manual_reminders'] ?? []);

        return back()->with('success', 'Task diperbarui.');
    }

    public function status(ChangeTodoStatusRequest $request, Todo $todo, ChangeTodoStatus $action): RedirectResponse
    {
        $statusAt = Carbon::parse($request->validated('status_at'), 'Asia/Jakarta')->utc();
        $action->handle($todo, $request->user(), TodoStatus::from($request->validated('status')), $statusAt);

        return back()->with('success', 'Status task diperbarui.');
    }

    public function destroy(Request $request, Todo $todo, DeleteTodo $action): RedirectResponse
    {
        $action->handle($todo, $request->user());

        return back()->with('success', 'Task dihapus permanen.');
    }
}
