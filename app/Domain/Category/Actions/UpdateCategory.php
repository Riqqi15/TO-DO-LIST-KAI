<?php

namespace App\Domain\Category\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Category\Models\Category;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateCategory
{
    use AuthorizesDomainAction;

    public function __construct(private RecordActivity $activity) {}

    public function handle(Category $category, User $actor, string $name): Category
    {
        $this->authorizeAbility($actor, 'update', $category);
        $slug = Str::slug($name);
        if (Category::where('workspace_id', $category->workspace_id)->where('slug', $slug)->whereKeyNot($category->id)->exists()) {
            throw ValidationException::withMessages(['name' => 'Kategori dengan nama tersebut sudah ada.']);
        }
        $old = $category->only(['name', 'slug']);
        $category->update(['name' => $name, 'slug' => $slug]);
        $this->activity->handle($category->workspace, $actor, 'category.updated', $category, null, ['old' => $old, 'new' => $category->only(['name', 'slug'])]);

        return $category;
    }
}
