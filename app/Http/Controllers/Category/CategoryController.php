<?php

namespace App\Http\Controllers\Category;

use App\Domain\Category\Actions\CreateCategory;
use App\Domain\Category\Actions\DeleteCategory;
use App\Domain\Category\Actions\UpdateCategory;
use App\Domain\Category\Models\Category;
use App\Domain\Workspace\Models\Workspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(StoreCategoryRequest $request, Workspace $workspace, CreateCategory $action): RedirectResponse
    {
        $action->handle($workspace, $request->user(), $request->validated('name'));

        return back()->with('success', 'Kategori dibuat.');
    }

    public function update(StoreCategoryRequest $request, Category $category, UpdateCategory $action): RedirectResponse
    {
        $action->handle($category, $request->user(), $request->validated('name'));

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Request $request, Category $category, DeleteCategory $action): RedirectResponse
    {
        $action->handle($category, $request->user());

        return back()->with('success', 'Kategori dihapus.');
    }
}
