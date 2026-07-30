<?php

namespace App\Domain\Category\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Category\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class DeleteCategory
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(Category $category, User $actor): void
    {
        if (! $actor->can('delete', $category)) {
            throw new AuthorizationException;
        }
        if ($category->todos()->exists()) {
            throw ValidationException::withMessages(['category' => 'Kategori tidak dapat dihapus selama masih digunakan task.']);
        }
        $snapshot = $category->only(['id', 'name', 'slug']);
        $this->activity->handle($category->workspace, $actor, 'category.deleted', $category, $snapshot);
        $category->delete();
    }
}
