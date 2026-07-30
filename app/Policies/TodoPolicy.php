<?php

namespace App\Policies;

use App\Domain\Todo\Models\Todo;
use App\Models\User;

class TodoPolicy
{
    public function view(User $user, Todo $todo): bool
    {
        return $todo->workspace->hasMember($user);
    }

    public function update(User $user, Todo $todo): bool
    {
        return $todo->workspace->hasMember($user);
    }

    public function delete(User $user, Todo $todo): bool
    {
        return $todo->created_by === $user->id || $todo->workspace->isOwner($user);
    }
}
