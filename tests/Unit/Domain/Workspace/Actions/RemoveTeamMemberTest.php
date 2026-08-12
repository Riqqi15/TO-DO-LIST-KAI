<?php

namespace Tests\Unit\Domain\Workspace\Actions;

use App\Domain\Workspace\Actions\CreateTeam;
use App\Domain\Workspace\Actions\ProvisionPersonalWorkspace;
use App\Domain\Workspace\Actions\RemoveTeamMember;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RemoveTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_remove_member_and_activity_is_recorded(): void
    {
        [$owner, $member, $team] = $this->teamContext();

        app(RemoveTeamMember::class)->handle($team, $owner, $member);

        $this->assertFalse($team->fresh()->hasMember($member));
        $this->assertDatabaseHas('activity_logs', ['action' => 'team.member_removed', 'actor_id' => $owner->id]);
    }

    public function test_member_cannot_remove_another_member(): void
    {
        [, $member, $team] = $this->teamContext();
        $other = User::factory()->create();
        $team->membershipRows()->create(['user_id' => $other->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);

        $this->expectException(AuthorizationException::class);
        app(RemoveTeamMember::class)->handle($team, $member, $other);
    }

    public function test_personal_workspace_cannot_remove_members(): void
    {
        $user = User::factory()->create();
        $personal = app(ProvisionPersonalWorkspace::class)->handle($user);

        $this->expectException(AuthorizationException::class);
        app(RemoveTeamMember::class)->handle($personal, $user, $user);
    }

    public function test_owner_cannot_remove_themselves(): void
    {
        [$owner, , $team] = $this->teamContext();

        $this->assertValidationFails(fn () => app(RemoveTeamMember::class)->handle($team, $owner, $owner));
        $this->assertTrue($team->fresh()->hasMember($owner));
    }

    public function test_non_member_cannot_be_removed(): void
    {
        [$owner, , $team] = $this->teamContext();

        $this->assertValidationFails(fn () => app(RemoveTeamMember::class)->handle($team, $owner, User::factory()->create()));
    }

    private function assertValidationFails(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a validation exception for user_id.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('user_id', $exception->errors());
        }
    }

    /**
     * @return array{0: User, 1: User, 2: Workspace}
     */
    private function teamContext(): array
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Keanggotaan');
        $team->membershipRows()->create(['user_id' => $member->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);

        return [$owner, $member, $team];
    }
}
