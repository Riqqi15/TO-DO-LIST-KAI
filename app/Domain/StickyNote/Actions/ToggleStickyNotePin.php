<?php

namespace App\Domain\StickyNote\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\StickyNote\Models\StickyNote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ToggleStickyNotePin
{
    use AuthorizesDomainAction;

    public function __construct(private RecordActivity $activity) {}

    public function handle(StickyNote $note, User $actor): StickyNote
    {
        $this->authorizeAbility($actor, 'update', $note);

        return DB::transaction(function () use ($note, $actor) {
            $lockedNote = StickyNote::query()->lockForUpdate()->findOrFail($note->id);
            $pinnedNotes = StickyNote::query()
                ->where('workspace_id', $lockedNote->workspace_id)
                ->whereNotNull('pinned_at')
                ->lockForUpdate()
                ->orderBy('pin_order')
                ->orderBy('id')
                ->get();

            if ($lockedNote->pinned_at) {
                $lockedNote->update(['pinned_at' => null, 'pin_order' => null]);
                $pinnedNotes->where('id', '!=', $lockedNote->id)->values()->each(
                    fn (StickyNote $pinned, int $index) => $pinned->update(['pin_order' => $index]),
                );
                $action = 'sticky_note.unpinned';
            } else {
                $lockedNote->update(['pinned_at' => now(), 'pin_order' => $pinnedNotes->count()]);
                $action = 'sticky_note.pinned';
            }

            $this->activity->handle($lockedNote->workspace, $actor, $action, $lockedNote, null, [
                'pinned_at' => $lockedNote->pinned_at?->toIso8601String(),
                'pin_order' => $lockedNote->pin_order,
            ]);

            return $lockedNote->refresh();
        });
    }
}
