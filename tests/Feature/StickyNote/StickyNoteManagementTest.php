<?php

namespace Tests\Feature\StickyNote;

use App\Domain\Category\Models\Category;
use App\Domain\StickyNote\Models\StickyNote;
use App\Domain\Workspace\Actions\CreateTeam;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StickyNoteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_members_can_collaboratively_edit_note(): void
    {
        [$owner, $member, $team] = $this->teamContext();
        $this->actingAs($owner)->post(route('sticky-notes.store', $team), ['content' => 'Ide awal', 'color' => 'blue'])->assertSessionHasNoErrors();
        $note = StickyNote::firstOrFail();
        $this->actingAs($member)->patch(route('sticky-notes.update', $note), ['content' => 'Ide hasil diskusi', 'color' => 'green'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sticky_notes', ['id' => $note->id, 'content' => 'Ide hasil diskusi', 'color' => 'green']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'sticky_note.updated', 'actor_id' => $member->id]);
    }

    public function test_note_remains_and_links_to_todo_after_conversion(): void
    {
        [$owner, $member, $team] = $this->teamContext();
        $this->actingAs($owner)->post(route('sticky-notes.store', $team), ['content' => 'Bahas progres mingguan'])->assertSessionHasNoErrors();
        $note = StickyNote::firstOrFail();
        $category = Category::where('is_system', true)->firstOrFail();
        $this->actingAs($member)->post(route('sticky-notes.convert', $note), [
            'category_id' => $category->id,
            'title' => 'Meeting progres mingguan',
            'deadline_at' => now('Asia/Jakarta')->addDays(14)->format('Y-m-d H:i:s'),
        ])->assertSessionHasNoErrors();
        $note->refresh();
        $this->assertNotNull($note->converted_to_todo_id);
        $this->assertNotNull($note->converted_at);
        $this->assertDatabaseHas('todos', ['id' => $note->converted_to_todo_id, 'description' => 'Bahas progres mingguan']);
    }

    public function test_only_creator_or_owner_can_delete_note(): void
    {
        [$owner, $member, $team] = $this->teamContext();
        $other = User::factory()->create();
        $team->membershipRows()->create(['user_id' => $other->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);
        $this->actingAs($member)->post(route('sticky-notes.store', $team), ['content' => 'Milik anggota'])->assertSessionHasNoErrors();
        $note = StickyNote::firstOrFail();
        $this->actingAs($other)->delete(route('sticky-notes.destroy', $note))->assertForbidden();
        $this->actingAs($owner)->delete(route('sticky-notes.destroy', $note))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sticky_notes', ['id' => $note->id]);
    }

    private function teamContext(): array
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Brainstorming');
        $team->membershipRows()->create(['user_id' => $member->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);

        return [$owner, $member, $team];
    }
}
