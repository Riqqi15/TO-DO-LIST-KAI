<?php

namespace Tests\Feature\Todo;

use App\Domain\ActivityLog\Models\ActivityLog;
use App\Domain\Category\Models\Category;
use App\Domain\Todo\Actions\CreateTodo;
use App\Domain\Workspace\Actions\ProvisionPersonalWorkspace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Tests\TestCase;

class TodoPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_page_only_exposes_member_workspaces_and_domain_data(): void
    {
        $user = User::factory()->create();
        $workspace = app(ProvisionPersonalWorkspace::class)->handle($user);
        $other = app(ProvisionPersonalWorkspace::class)->handle(User::factory()->create());
        $category = Category::where('is_system', true)->firstOrFail();
        app(CreateTodo::class)->handle($workspace, $user, $category, ['title' => 'Task tampil', 'deadline_at' => now('Asia/Jakarta')->addDays(14)->format('Y-m-d H:i:s')]);

        $this->actingAs($user)->get(route('todo.index', ['workspace' => $workspace->id]))
            ->assertInertia(fn (Assert $page) => $page->component('Todo/Index')->has('workspaces', 1)->has('todos', 1)->where('timezone', 'Asia/Jakarta'));
        $this->actingAs($user)->get(route('todo.index', ['workspace' => $other->id]))->assertForbidden();
    }

    public function test_calendar_is_derived_from_task_deadlines(): void
    {
        $user = User::factory()->create();
        $workspace = app(ProvisionPersonalWorkspace::class)->handle($user);
        $category = Category::where('is_system', true)->firstOrFail();
        app(CreateTodo::class)->handle($workspace, $user, $category, ['title' => 'Agenda deadline', 'deadline_at' => now('Asia/Jakarta')->addDays(14)->format('Y-m-d H:i:s')]);
        $this->actingAs($user)->getJson(route('todos.calendar', $workspace))
            ->assertOk()->assertJsonPath('timezone', 'Asia/Jakarta')->assertJsonCount(1, 'events')->assertJsonPath('events.0.title', 'Agenda deadline');
    }

    public function test_activity_logs_cannot_be_updated_or_deleted_through_models(): void
    {
        $user = User::factory()->create();
        $workspace = app(ProvisionPersonalWorkspace::class)->handle($user);
        $log = ActivityLog::create(['workspace_id' => $workspace->id, 'actor_id' => $user->id, 'action' => 'test.created', 'subject_type' => User::class, 'subject_id' => $user->id]);
        $this->expectException(LogicException::class);
        $log->update(['action' => 'tampered']);
    }
}
