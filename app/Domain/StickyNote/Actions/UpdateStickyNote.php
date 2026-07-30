<?php

namespace App\Domain\StickyNote\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\StickyNote\Models\StickyNote;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateStickyNote
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(StickyNote $note, User $actor, array $data): StickyNote
    {
        if (! $actor->can('update', $note)) {
            throw new AuthorizationException;
        }
        $old = $note->only(['content', 'color']);
        $note->update(['content' => $data['content'], 'color' => $data['color'] ?? $note->color]);
        $this->activity->handle($note->workspace, $actor, 'sticky_note.updated', $note, null, ['old' => $old, 'new' => $note->only(['content', 'color'])]);

        return $note;
    }
}
