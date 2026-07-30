<?php

namespace App\Policies;

use App\Domain\StickyNote\Models\StickyNote;
use App\Models\User;

class StickyNotePolicy
{
    public function update(User $user, StickyNote $note): bool
    {
        return $note->workspace->hasMember($user);
    }

    public function delete(User $user, StickyNote $note): bool
    {
        return $note->created_by === $user->id || $note->workspace->isOwner($user);
    }
}
