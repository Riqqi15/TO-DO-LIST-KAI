<?php

namespace Tests\Feature\Workspace;

use App\Domain\Workspace\Actions\CreateTeam;
use App\Domain\Workspace\Actions\GenerateTeamInviteCode;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMembershipRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_join_accepts_lowercase_and_padded_code(): void
    {
        $owner = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Gabung');
        $invite = app(GenerateTeamInviteCode::class)->handle($team, $owner);
        $newMember = User::factory()->create();

        $this->actingAs($newMember)
            ->post(route('teams.join'), ['code' => '  '.strtolower($invite['code']).' '])
            ->assertSessionHasNoErrors();

        $this->assertTrue($team->fresh()->hasMember($newMember));
    }

    public function test_join_code_must_be_eight_alphanumeric_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('teams.join'), ['code' => 'ABC'])->assertSessionHasErrors('code');
        $this->actingAs($user)->post(route('teams.join'), ['code' => 'ABCD-123'])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('workspace_members', 0);
    }

    public function test_unverified_user_cannot_join_team(): void
    {
        $owner = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Terverifikasi');
        $invite = app(GenerateTeamInviteCode::class)->handle($team, $owner);

        $this->actingAs(User::factory()->unverified()->create())
            ->post(route('teams.join'), ['code' => $invite['code']])
            ->assertRedirect(route('verification.notice'));

        $this->assertSame(1, $team->fresh()->membershipRows()->count());
    }

    public function test_owner_can_remove_member_through_route(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Keluarkan');
        $team->membershipRows()->create(['user_id' => $member->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);

        $this->actingAs($owner)->delete(route('teams.members.destroy', [$team, $member]))->assertSessionHasNoErrors();

        $this->assertFalse($team->fresh()->hasMember($member));
        $this->assertDatabaseHas('activity_logs', ['action' => 'team.member_removed', 'actor_id' => $owner->id]);
    }

    public function test_member_cannot_remove_other_member_through_route(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $other = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Aman');
        foreach ([$member, $other] as $user) {
            $team->membershipRows()->create(['user_id' => $user->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);
        }

        $this->actingAs($member)->delete(route('teams.members.destroy', [$team, $other]))->assertForbidden();

        $this->assertTrue($team->fresh()->hasMember($other));
    }
}
