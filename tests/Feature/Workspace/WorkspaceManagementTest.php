<?php

namespace Tests\Feature\Workspace;

use App\Domain\ActivityLog\Models\ActivityLog;
use App\Domain\Workspace\Actions\CreateTeam;
use App\Domain\Workspace\Actions\GenerateTeamInviteCode;
use App\Domain\Workspace\Actions\JoinTeam;
use App\Domain\Workspace\Actions\ProvisionPersonalWorkspace;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Domain\Workspace\Enums\WorkspaceType;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkspaceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_workspace_provisioning_is_idempotent(): void
    {
        $user = User::factory()->create();
        $action = app(ProvisionPersonalWorkspace::class);
        $action->handle($user);
        $action->handle($user);
        $this->assertDatabaseCount('workspaces', 1);
        $this->assertDatabaseHas('workspaces', ['created_by' => $user->id, 'type' => WorkspaceType::Personal->value, 'member_limit' => 1]);
        $this->assertDatabaseHas('workspace_members', ['user_id' => $user->id, 'role' => WorkspaceRole::Owner->value]);
    }

    public function test_verified_user_can_create_team_with_default_capacity(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('teams.store'), ['name' => 'Tim Evaluasi'])->assertRedirect();
        $workspace = Workspace::where('name', 'Tim Evaluasi')->firstOrFail();
        $this->assertSame(5, $workspace->member_limit);
        $this->assertTrue($workspace->isOwner($owner));
        $this->assertDatabaseHas('activity_logs', ['workspace_id' => $workspace->id, 'action' => 'team.created']);
    }

    public function test_invite_code_is_reusable_until_it_expires(): void
    {
        $owner = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Bersama');
        $invite = app(GenerateTeamInviteCode::class)->handle($team, $owner);
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        app(JoinTeam::class)->handle($memberA, $invite['code']);
        app(JoinTeam::class)->handle($memberB, $invite['code']);
        $this->assertTrue($team->hasMember($memberA));
        $this->assertTrue($team->hasMember($memberB));
    }

    public function test_expired_invite_returns_specific_validation_error(): void
    {
        $owner = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Lama');
        $invite = app(GenerateTeamInviteCode::class)->handle($team, $owner);
        $team->invites()->update(['expires_at' => now()->subMinute()]);
        try {
            app(JoinTeam::class)->handle(User::factory()->create(), $invite['code']);
            $this->fail('Validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame('Kode tim sudah kedaluwarsa.', $exception->errors()['code'][0]);
        }
    }

    public function test_team_capacity_accepts_only_five_or_ten_and_cannot_shrink_below_members(): void
    {
        $owner = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Besar');
        $this->actingAs($owner)->patch(route('teams.capacity', $team), ['member_limit' => 10])->assertSessionHasNoErrors();
        $team->refresh();
        $this->assertSame(10, $team->member_limit);
        foreach (range(1, 5) as $i) {
            $team->membershipRows()->create(['user_id' => User::factory()->create()->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);
        }
        $this->actingAs($owner)->patch(route('teams.capacity', $team), ['member_limit' => 5])->assertSessionHasErrors('member_limit');
    }

    public function test_owner_must_transfer_ownership_before_leaving(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Transfer');
        $team->membershipRows()->create(['user_id' => $member->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);
        $this->actingAs($owner)->delete(route('teams.leave', $team))->assertSessionHasErrors('team');
        $this->actingAs($owner)->patch(route('teams.transfer', $team), ['user_id' => $member->id])->assertSessionHasNoErrors();
        $this->assertTrue($team->fresh()->isOwner($member));
        $this->actingAs($owner)->delete(route('teams.leave', $team))->assertSessionHasNoErrors();
        $this->assertFalse($team->fresh()->hasMember($owner));
    }

    public function test_team_deletion_requires_exact_confirmation_and_keeps_activity_snapshot(): void
    {
        $owner = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Rahasia');
        $this->actingAs($owner)->delete(route('teams.destroy', $team), ['confirmation' => 'salah'])->assertSessionHasErrors('confirmation');
        $this->assertDatabaseHas('workspaces', ['id' => $team->id]);
        $this->actingAs($owner)->delete(route('teams.destroy', $team), ['confirmation' => 'konfirmasi hapus tim Tim Rahasia'])->assertRedirect(route('todo.index'));
        $this->assertDatabaseMissing('workspaces', ['id' => $team->id]);
        $log = ActivityLog::where('action', 'team.deleted')->firstOrFail();
        $this->assertNull($log->workspace_id);
        $this->assertSame('Tim Rahasia', $log->snapshot['name']);
    }
}
