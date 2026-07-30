<?php

namespace App\Policies;

use App\Domain\Workspace\Models\Workspace;
use App\Models\User;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    public function createContent(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    public function manageTeam(User $user, Workspace $workspace): bool
    {
        return $workspace->isTeam() && $workspace->isOwner($user);
    }
}
