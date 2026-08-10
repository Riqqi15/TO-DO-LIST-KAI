<?php

namespace App\Http\Controllers\Todo;

use App\Domain\Todo\Actions\AddTodoNote;
use App\Domain\Todo\Actions\DeleteTodoNote;
use App\Domain\Todo\Models\Todo;
use App\Domain\Todo\Models\TodoNote;
use App\Http\Controllers\Controller;
use App\Http\Requests\Todo\TodoNoteRequest;
use Illuminate\Http\RedirectResponse;

class TodoNoteController extends Controller
{
    public function store(TodoNoteRequest $request, Todo $todo, AddTodoNote $action): RedirectResponse
    {
        $action->handle($todo, $request->user(), $request->validated('body'));
        return back()->with('success', 'Catatan berhasil ditambahkan.');
    }

    public function destroy(TodoNote $note, DeleteTodoNote $action): RedirectResponse
    {
        $action->handle($note, request()->user());
        return back()->with('success', 'Catatan berhasil dihapus.');
    }
}
