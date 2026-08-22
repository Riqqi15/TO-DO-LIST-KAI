<?php

namespace App\Domain\Category\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Category\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateCategory
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(Category $category, User $actor, string $name, ?string $color = null): Category
    {
        if (! $actor->can('update', $category)) {
            throw new AuthorizationException;
        }
        $slug = Str::slug($name);
        if (Category::where('workspace_id', $category->workspace_id)->where('slug', $slug)->whereKeyNot($category->id)->exists()) {
            throw ValidationException::withMessages(['name' => 'Kategori dengan nama tersebut sudah ada.']);
        }
        $old = $category->only(['name', 'slug', 'color']);
        $category->update(['name' => $name, 'slug' => $slug, 'color' => $color]);
        $this->activity->handle($category->workspace, $actor, 'category.updated', $category, null, ['old' => $old, 'new' => $category->only(['name', 'slug', 'color'])]);

        return $category;
    }
}
