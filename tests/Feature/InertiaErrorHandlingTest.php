<?php

namespace Tests\Feature;

use App\Domain\Category\Models\Category;
use App\Domain\Todo\Actions\CreateTodo;
use App\Domain\Workspace\Actions\ProvisionPersonalWorkspace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InertiaErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_forbidden_inertia_action_redirects_back_with_error_flash(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $workspace = app(ProvisionPersonalWorkspace::class)->handle($owner);
        $todo = app(CreateTodo::class)->handle($workspace, $owner, Category::where('is_system', true)->firstOrFail(), [
            'title' => 'Task milik orang lain',
            'deadline_at' => now('Asia/Jakarta')->addDays(10)->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($intruder)
            ->from(route('todo.index'))
            ->withHeader('X-Inertia', 'true')
            ->delete(route('todos.destroy', $todo));

        $response->assertStatus(303);
        $response->assertRedirect(route('todo.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('todos', ['id' => $todo->id]);
    }

    public function test_forbidden_non_inertia_action_keeps_original_status(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $workspace = app(ProvisionPersonalWorkspace::class)->handle($owner);
        $todo = app(CreateTodo::class)->handle($workspace, $owner, Category::where('is_system', true)->firstOrFail(), [
            'title' => 'Task milik orang lain',
            'deadline_at' => now('Asia/Jakarta')->addDays(10)->format('Y-m-d H:i:s'),
        ]);

        $this->actingAs($intruder)->delete(route('todos.destroy', $todo))->assertForbidden();
    }
}
