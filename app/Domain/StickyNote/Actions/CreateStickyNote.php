<?php

namespace App\Domain\StickyNote\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\StickyNote\Models\StickyNote;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;

class CreateStickyNote
{
    use AuthorizesDomainAction;

    public function __construct(private RecordActivity $activity) {}

    public function handle(Workspace $workspace, User $actor, array $data): StickyNote
    {
        $this->authorizeWorkspaceMember($workspace, $actor);
        $note = StickyNote::create(['workspace_id' => $workspace->id, 'created_by' => $actor->id, 'content' => $data['content'], 'color' => $data['color'] ?? 'yellow']);
        $this->activity->handle($workspace, $actor, 'sticky_note.created', $note, $note->only(['id', 'content', 'color']));

        return $note;
    }
}
