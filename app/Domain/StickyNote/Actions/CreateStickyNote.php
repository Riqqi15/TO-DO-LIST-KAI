<?php

namespace App\Domain\StickyNote\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\StickyNote\Models\StickyNote;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CreateStickyNote
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(Workspace $workspace, User $actor, array $data): StickyNote
    {
        if (! $workspace->hasMember($actor)) {
            throw new AuthorizationException;
        }
        $note = StickyNote::create(['workspace_id' => $workspace->id, 'created_by' => $actor->id, 'content' => $data['content'], 'color' => $data['color'] ?? 'yellow']);
        $this->activity->handle($workspace, $actor, 'sticky_note.created', $note, $note->only(['id', 'content', 'color']));

        return $note;
    }
}
