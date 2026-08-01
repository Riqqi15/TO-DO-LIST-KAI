<?php

namespace Tests\Feature\StickyNote;

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

    public function test_members_can_pin_unpin_and_reorder_notes(): void
    {
        [$owner, $member, $team] = $this->teamContext();
        foreach (['Pertama', 'Kedua', 'Ketiga'] as $content) {
            $this->actingAs($owner)->post(route('sticky-notes.store', $team), ['content' => $content])->assertSessionHasNoErrors();
        }
        [$first, $second] = StickyNote::orderBy('id')->take(2)->get();

        $this->actingAs($member)->patch(route('sticky-notes.pin', $first))->assertSessionHasNoErrors();
        $this->actingAs($member)->patch(route('sticky-notes.pin', $second))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sticky_notes', ['id' => $first->id, 'pin_order' => 0]);
        $this->assertDatabaseHas('sticky_notes', ['id' => $second->id, 'pin_order' => 1]);

        $this->actingAs($member)->patch(route('sticky-notes.reorder', $team), [
            'note_ids' => [$second->id, $first->id],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sticky_notes', ['id' => $second->id, 'pin_order' => 0]);
        $this->assertDatabaseHas('sticky_notes', ['id' => $first->id, 'pin_order' => 1]);

        $this->actingAs($member)->patch(route('sticky-notes.pin', $second))->assertSessionHasNoErrors();
        $this->assertNull($second->fresh()->pinned_at);
        $this->assertNull($second->fresh()->pin_order);
        $this->assertSame(0, $first->fresh()->pin_order);
        $this->assertDatabaseHas('activity_logs', ['action' => 'sticky_note.pins_reordered', 'actor_id' => $member->id]);
    }

    public function test_reorder_rejects_duplicate_incomplete_and_cross_workspace_ids(): void
    {
        [$owner, $member, $team] = $this->teamContext();
        $this->actingAs($owner)->post(route('sticky-notes.store', $team), ['content' => 'Satu']);
        $this->actingAs($owner)->post(route('sticky-notes.store', $team), ['content' => 'Dua']);
        $notes = StickyNote::where('workspace_id', $team->id)->orderBy('id')->get();
        foreach ($notes as $note) {
            $this->actingAs($member)->patch(route('sticky-notes.pin', $note));
        }

        $this->actingAs($member)->patch(route('sticky-notes.reorder', $team), [
            'note_ids' => [$notes[0]->id, $notes[0]->id],
        ])->assertSessionHasErrors('note_ids.1');
        $this->actingAs($member)->patch(route('sticky-notes.reorder', $team), [
            'note_ids' => [$notes[0]->id],
        ])->assertSessionHasErrors('note_ids');

        [$otherOwner, , $otherTeam] = $this->teamContext();
        $this->actingAs($otherOwner)->post(route('sticky-notes.store', $otherTeam), ['content' => 'Workspace lain']);
        $otherNote = StickyNote::where('workspace_id', $otherTeam->id)->firstOrFail();
        $this->actingAs($otherOwner)->patch(route('sticky-notes.pin', $otherNote));
        $this->actingAs($member)->patch(route('sticky-notes.reorder', $team), [
            'note_ids' => [$notes[0]->id, $otherNote->id],
        ])->assertSessionHasErrors('note_ids');
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
