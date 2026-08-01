<?php

namespace App\Http\Controllers\StickyNote;

use App\Domain\StickyNote\Actions\CreateStickyNote;
use App\Domain\StickyNote\Actions\DeleteStickyNote;
use App\Domain\StickyNote\Actions\ReorderPinnedStickyNotes;
use App\Domain\StickyNote\Actions\ToggleStickyNotePin;
use App\Domain\StickyNote\Actions\UpdateStickyNote;
use App\Domain\StickyNote\Models\StickyNote;
use App\Domain\Workspace\Models\Workspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\StickyNote\ReorderPinnedStickyNotesRequest;
use App\Http\Requests\StickyNote\StoreStickyNoteRequest;
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

    public function togglePin(Request $request, StickyNote $note, ToggleStickyNotePin $action): RedirectResponse
    {
        $action->handle($note, $request->user());

        return back()->with('success', $note->pinned_at ? 'Pin sticky note dilepas.' : 'Sticky note dipin.');
    }

    public function reorder(ReorderPinnedStickyNotesRequest $request, Workspace $workspace, ReorderPinnedStickyNotes $action): RedirectResponse
    {
        $action->handle($workspace, $request->user(), $request->validated('note_ids'));

        return back()->with('success', 'Urutan sticky note disimpan.');
    }
}
