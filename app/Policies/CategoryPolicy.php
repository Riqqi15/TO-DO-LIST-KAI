<?php

namespace App\Policies;

use App\Domain\Category\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function update(User $user, Category $category): bool
    {
        return ! $category->is_system && $category->workspace && ($category->created_by === $user->id || $category->workspace->isOwner($user));
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->update($user, $category);
    }
}
