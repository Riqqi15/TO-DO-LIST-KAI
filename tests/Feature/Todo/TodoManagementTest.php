<?php

namespace Tests\Feature\Todo;

use App\Domain\Category\Actions\CreateCategory;
use App\Domain\Category\Models\Category;
use App\Domain\Reminder\Enums\ReminderKind;
use App\Domain\Reminder\Enums\ReminderStatus;
use App\Domain\Todo\Actions\CreateTodo;
use App\Domain\Todo\Enums\TodoStatus;
use App\Domain\Workspace\Actions\CreateTeam;
use App\Domain\Workspace\Actions\ProvisionPersonalWorkspace;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_category_crud_and_used_category_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $workspace = app(ProvisionPersonalWorkspace::class)->handle($user);
        $this->actingAs($user)->post(route('categories.store', $workspace), ['name' => 'Dokumentasi'])->assertSessionHasNoErrors();
        $category = Category::where('workspace_id', $workspace->id)->firstOrFail();
        $this->actingAs($user)->patch(route('categories.update', $category), ['name' => 'Dokumen'])->assertSessionHasNoErrors();
        app(CreateTodo::class)->handle($workspace, $user, $category->fresh(), [
            'title' => 'Susun laporan',
            'deadline_at' => now('Asia/Jakarta')->addDays(10)->format('Y-m-d H:i:s'),
        ]);
        $this->actingAs($user)->delete(route('categories.destroy', $category))->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Dokumen']);
    }

    public function test_system_categories_exist_and_cannot_be_modified(): void
    {
        $user = User::factory()->create();
        $category = Category::where('is_system', true)->firstOrFail();
        $this->actingAs($user)->patch(route('categories.update', $category), ['name' => 'Diubah'])->assertForbidden();
    }

    public function test_todo_creates_two_future_automatic_reminders(): void
    {
        [$user, $workspace, $category] = $this->personalContext();
        $todo = app(CreateTodo::class)->handle($workspace, $user, $category, [
            'title' => 'Meeting evaluasi',
            'description' => 'Bahas progres.',
            'deadline_at' => now('Asia/Jakarta')->addDays(14)->format('Y-m-d H:i:s'),
        ]);
        $this->assertSame(TodoStatus::BelumDikerjakan, $todo->status);
        $this->assertCount(2, $todo->reminders);
        $this->assertEqualsCanonicalizing(
            [ReminderKind::AutomaticSevenDays->value, ReminderKind::AutomaticThreeDays->value],
            $todo->reminders->pluck('kind')->map->value->all(),
        );
    }

    public function test_near_deadline_requires_manual_reminder_and_accepts_flexible_time(): void
    {
        [$user, $workspace, $category] = $this->personalContext();
        $deadline = now('Asia/Jakarta')->addDay();
        $this->actingAs($user)->post(route('todos.store', $workspace), [
            'category_id' => $category->id,
            'title' => 'Tugas mendesak',
            'deadline_at' => $deadline->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('manual_reminders');
        $this->actingAs($user)->post(route('todos.store', $workspace), [
            'category_id' => $category->id,
            'title' => 'Tugas mendesak',
            'deadline_at' => $deadline->format('Y-m-d H:i:s'),
            'manual_reminders' => [now('Asia/Jakarta')->addHours(2)->format('Y-m-d H:i:s')],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('todo_reminders', ['kind' => ReminderKind::Manual->value, 'status' => ReminderStatus::Scheduled->value]);
    }

    public function test_any_member_can_change_status_and_completion_cancels_reminders(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Kolaborasi');
        $team->membershipRows()->create(['user_id' => $member->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);
        $category = Category::where('is_system', true)->firstOrFail();
        $todo = app(CreateTodo::class)->handle($team, $owner, $category, ['title' => 'Task bersama', 'deadline_at' => now('Asia/Jakarta')->addDays(14)->format('Y-m-d H:i:s')]);
        $this->actingAs($member)->patch(route('todos.status', $todo), ['status' => TodoStatus::SedangDikerjakan->value])->assertSessionHasNoErrors();
        $this->actingAs($member)->patch(route('todos.status', $todo), ['status' => TodoStatus::Selesai->value])->assertSessionHasNoErrors();
        $this->assertSame(TodoStatus::Selesai, $todo->fresh()->status);
        $this->assertSame(0, $todo->reminders()->where('status', ReminderStatus::Scheduled->value)->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'todo.status_changed', 'actor_id' => $member->id]);
    }

    public function test_non_creator_member_cannot_delete_team_todo_but_owner_can(): void
    {
        $owner = User::factory()->create();
        $creator = User::factory()->create();
        $other = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Delete');
        foreach ([$creator, $other] as $user) {
            $team->membershipRows()->create(['user_id' => $user->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);
        }
        $category = Category::where('is_system', true)->firstOrFail();
        $todo = app(CreateTodo::class)->handle($team, $creator, $category, ['title' => 'Task aman', 'deadline_at' => now('Asia/Jakarta')->addDays(14)->format('Y-m-d H:i:s')]);
        $this->actingAs($other)->delete(route('todos.destroy', $todo))->assertForbidden();
        $this->actingAs($owner)->delete(route('todos.destroy', $todo))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'todo.deleted', 'actor_id' => $owner->id]);
    }

    public function test_deadline_can_move_near_when_manual_reminder_is_submitted_together(): void
    {
        [$user, $workspace, $category] = $this->personalContext();
        $todo = app(CreateTodo::class)->handle($workspace, $user, $category, ['title' => 'Deadline lama', 'deadline_at' => now('Asia/Jakarta')->addDays(14)->format('Y-m-d H:i:s')]);
        $this->actingAs($user)->put(route('todos.update', $todo), [
            'category_id' => $category->id,
            'title' => 'Deadline baru',
            'deadline_at' => now('Asia/Jakarta')->addDay()->format('Y-m-d H:i:s'),
            'manual_reminders' => [now('Asia/Jakarta')->addHours(3)->format('Y-m-d H:i:s')],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('todo_reminders', ['todo_id' => $todo->id, 'kind' => ReminderKind::Manual->value, 'status' => ReminderStatus::Scheduled->value]);
    }

    public function test_completed_near_deadline_task_can_reopen_with_new_manual_reminder(): void
    {
        [$user, $workspace, $category] = $this->personalContext();
        $todo = app(CreateTodo::class)->handle($workspace, $user, $category, [
            'title' => 'Task salah selesai',
            'deadline_at' => now('Asia/Jakarta')->addDay()->format('Y-m-d H:i:s'),
        ], [now('Asia/Jakarta')->addHour()->format('Y-m-d H:i:s')]);
        $this->actingAs($user)->patch(route('todos.status', $todo), ['status' => TodoStatus::Selesai->value])->assertSessionHasNoErrors();
        $todo->reminders()->update(['scheduled_at' => now()->subMinute()]);
        $this->actingAs($user)->patch(route('todos.status', $todo), [
            'status' => TodoStatus::SedangDikerjakan->value,
            'manual_reminder_at' => now('Asia/Jakarta')->addHours(2)->format('Y-m-d H:i:s'),
        ])->assertSessionHasNoErrors();
        $this->assertSame(TodoStatus::SedangDikerjakan, $todo->fresh()->status);
        $this->assertTrue($todo->reminders()->where('status', ReminderStatus::Scheduled->value)->where('scheduled_at', '>', now())->exists());
    }

    private function personalContext(): array
    {
        $user = User::factory()->create();
        $workspace = app(ProvisionPersonalWorkspace::class)->handle($user);
        $category = app(CreateCategory::class)->handle($workspace, $user, 'Pekerjaan');

        return [$user, $workspace, $category];
    }
}
