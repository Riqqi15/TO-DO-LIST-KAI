<?php

namespace App\Domain\StickyNote\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\StickyNote\Models\StickyNote;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteStickyNote
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(StickyNote $note, User $actor): void
    {
        if (! $actor->can('delete', $note)) {
            throw new AuthorizationException;
        }
        $this->activity->handle($note->workspace, $actor, 'sticky_note.deleted', $note, $note->only(['id', 'content', 'color']));
        $note->delete();
    }
}
