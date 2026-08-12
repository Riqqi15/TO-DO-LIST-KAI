<?php

namespace Tests\Feature\Todo;

use App\Domain\Category\Models\Category;
use App\Domain\Todo\Actions\CreateTodo;
use App\Domain\Todo\Models\Todo;
use App\Domain\Todo\Models\TodoNote;
use App\Domain\Workspace\Actions\CreateTeam;
use App\Domain\Workspace\Actions\ProvisionPersonalWorkspace;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_note_to_todo()
    {
        $user = User::factory()->create();
        $workspace = app(ProvisionPersonalWorkspace::class)->handle($user);
        $category = Category::where('is_system', true)->first();
        $todo = app(CreateTodo::class)->handle($workspace, $user, $category, [
            'title' => 'Test',
            'deadline_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ], [now()->addDays(1)->format('Y-m-d H:i:s')]);

        $response = $this->actingAs($user)->post(route('todos.notes.store', $todo), [
            'body' => 'Hari pertama sangat produktif.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('todo_notes', [
            'todo_id' => $todo->id,
            'created_by' => $user->id,
            'body' => 'Hari pertama sangat produktif.',
        ]);
    }

    public function test_user_can_delete_note()
    {
        $user = User::factory()->create();
        $workspace = app(ProvisionPersonalWorkspace::class)->handle($user);
        $category = Category::where('is_system', true)->first();
        $todo = app(CreateTodo::class)->handle($workspace, $user, $category, [
            'title' => 'Test',
            'deadline_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ], [now()->addDays(1)->format('Y-m-d H:i:s')]);
        $note = TodoNote::create(['todo_id' => $todo->id, 'created_by' => $user->id, 'body' => 'Catatan tes']);

        $response = $this->actingAs($user)->delete(route('todos.notes.destroy', $note));

        $response->assertRedirect();
        $this->assertDatabaseMissing('todo_notes', ['id' => $note->id]);
    }

    public function test_team_member_cannot_delete_note_of_another_member_but_owner_can()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Brainstorming');
        $team->membershipRows()->create(['user_id' => $member->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);
        $category = Category::where('is_system', true)->first();
        $todo = app(CreateTodo::class)->handle($team, $owner, $category, [
            'title' => 'Test',
            'deadline_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ], [now()->addDays(1)->format('Y-m-d H:i:s')]);
        $note = TodoNote::create(['todo_id' => $todo->id, 'created_by' => $owner->id, 'body' => 'Catatan owner']);

        $this->actingAs($member)->delete(route('todos.notes.destroy', $note))->assertForbidden();
        $this->assertDatabaseHas('todo_notes', ['id' => $note->id]);

        $this->actingAs($owner)->delete(route('todos.notes.destroy', $note))->assertRedirect();
        $this->assertDatabaseMissing('todo_notes', ['id' => $note->id]);
    }

    public function test_non_member_cannot_add_note()
    {
        $user = User::factory()->create();
        $workspace = app(ProvisionPersonalWorkspace::class)->handle($user);
        $category = Category::where('is_system', true)->first();
        $todo = app(CreateTodo::class)->handle($workspace, $user, $category, [
            'title' => 'Test',
            'deadline_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ], [now()->addDays(1)->format('Y-m-d H:i:s')]);
        
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->post(route('todos.notes.store', $todo), [
            'body' => 'Hari pertama sangat produktif.',
        ]);

        $response->assertForbidden();
    }
}
