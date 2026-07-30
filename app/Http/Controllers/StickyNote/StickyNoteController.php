<?php

namespace App\Http\Controllers\StickyNote;

use App\Domain\Category\Models\Category;
use App\Domain\StickyNote\Actions\ConvertStickyNoteToTodo;
use App\Domain\StickyNote\Actions\CreateStickyNote;
use App\Domain\StickyNote\Actions\DeleteStickyNote;
use App\Domain\StickyNote\Actions\UpdateStickyNote;
use App\Domain\StickyNote\Models\StickyNote;
use App\Domain\Workspace\Models\Workspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\StickyNote\StoreStickyNoteRequest;
use App\Http\Requests\Todo\StoreTodoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StickyNoteController extends Controller
{
    public function store(StoreStickyNoteRequest $request, Workspace $workspace, CreateStickyNote $action): RedirectResponse
    {
        $action->handle($workspace, $request->user(), $request->validated());

        return back()->with('success', 'Sticky note dibuat.');
    }

    public function update(StoreStickyNoteRequest $request, StickyNote $note, UpdateStickyNote $action): RedirectResponse
    {
        $action->handle($note, $request->user(), $request->validated());

        return back()->with('success', 'Sticky note diperbarui.');
    }

    public function destroy(Request $request, StickyNote $note, DeleteStickyNote $action): RedirectResponse
    {
        $action->handle($note, $request->user());

        return back()->with('success', 'Sticky note dihapus permanen.');
    }

    public function convert(StoreTodoRequest $request, StickyNote $note, ConvertStickyNoteToTodo $action): RedirectResponse
    {
        $data = $request->validated();
        $todo = $action->handle($note, $request->user(), Category::findOrFail($data['category_id']), $data, $data['manual_reminders'] ?? []);

        return back()->with('success', 'Sticky note berhasil dijadikan task.')->with('todo_id', $todo->id);
    }
}
