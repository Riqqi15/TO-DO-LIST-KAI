<?php

namespace App\Providers;

use App\Domain\Category\Models\Category;
use App\Domain\StickyNote\Models\StickyNote;
use App\Domain\Todo\Models\Todo;
use App\Domain\Workspace\Actions\ProvisionPersonalWorkspace;
use App\Domain\Workspace\Models\Workspace;
use App\Policies\CategoryPolicy;
use App\Policies\StickyNotePolicy;
use App\Policies\TodoPolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);
        Gate::policy(Todo::class, TodoPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(StickyNote::class, StickyNotePolicy::class);

        Event::listen(Verified::class, function (Verified $event): void {
            app(ProvisionPersonalWorkspace::class)->handle($event->user);
        });
    }
}
