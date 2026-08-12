<?php

namespace App\Domain\StickyNote\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\StickyNote\Models\StickyNote;
use App\Models\User;

class DeleteStickyNote
{
    use AuthorizesDomainAction;

    public function __construct(private RecordActivity $activity) {}

    public function handle(StickyNote $note, User $actor): void
    {
        $this->authorizeAbility($actor, 'delete', $note);
        $this->activity->handle($note->workspace, $actor, 'sticky_note.deleted', $note, $note->only(['id', 'content', 'color']));
        $note->delete();
    }
}
