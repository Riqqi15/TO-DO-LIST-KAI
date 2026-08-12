<?php

namespace App\Domain\Shared\Concerns;

use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesDomainAction
{
    protected function authorizeAbility(User $actor, string $ability, Model $subject): void
    {
        if (! $actor->can($ability, $subject)) {
            throw new AuthorizationException;
        }
    }

    protected function authorizeWorkspaceMember(Workspace $workspace, User $actor): void
    {
        if (! $workspace->hasMember($actor)) {
            throw new AuthorizationException;
        }
    }

    protected function authorizeTeamOwner(Workspace $workspace, User $actor): void
    {
        if (! $workspace->isTeam() || ! $workspace->isOwner($actor)) {
            throw new AuthorizationException;
        }
    }
}
