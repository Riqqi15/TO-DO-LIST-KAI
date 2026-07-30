<?php

namespace App\Domain\Category\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Category\Models\Category;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateCategory
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(Workspace $workspace, User $actor, string $name): Category
    {
        if (! $workspace->hasMember($actor)) {
            throw new AuthorizationException;
        }
        $slug = Str::slug($name);
        if (Category::where('workspace_id', $workspace->id)->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'Kategori dengan nama tersebut sudah ada.']);
        }
        $category = Category::create(['workspace_id' => $workspace->id, 'created_by' => $actor->id, 'name' => $name, 'slug' => $slug, 'is_system' => false]);
        $this->activity->handle($workspace, $actor, 'category.created', $category, $category->only(['id', 'name', 'slug']));

        return $category;
    }
}
