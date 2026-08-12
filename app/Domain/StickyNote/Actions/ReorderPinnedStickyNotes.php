<?php

namespace App\Domain\StickyNote\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\StickyNote\Models\StickyNote;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderPinnedStickyNotes
{
    use AuthorizesDomainAction;

    public function __construct(private RecordActivity $activity) {}

    public function handle(Workspace $workspace, User $actor, array $noteIds): void
    {
        $this->authorizeWorkspaceMember($workspace, $actor);

        DB::transaction(function () use ($workspace, $actor, $noteIds) {
            $pinned = StickyNote::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('pinned_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $submitted = collect($noteIds)->map(fn ($id) => (int) $id);
            if ($submitted->duplicates()->isNotEmpty() || $submitted->sort()->values()->all() !== $pinned->keys()->sort()->values()->all()) {
                throw ValidationException::withMessages(['note_ids' => 'Urutan harus memuat semua sticky note yang dipin pada workspace ini.']);
            }

            $submitted->values()->each(function (int $id, int $index) use ($pinned) {
                $pinned->get($id)->update(['pin_order' => $index]);
            });

            $this->activity->handle($workspace, $actor, 'sticky_note.pins_reordered', $workspace, null, [
                'note_ids' => $submitted->values()->all(),
            ]);
        });
    }
}
