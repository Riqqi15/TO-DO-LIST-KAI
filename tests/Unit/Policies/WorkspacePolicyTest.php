<?php

namespace Tests\Unit\Policies;

use App\Domain\Workspace\Actions\CreateTeam;
use App\Domain\Workspace\Actions\ProvisionPersonalWorkspace;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Models\User;
use App\Policies\WorkspacePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspacePolicyTest extends TestCase
{
    use RefreshDatabase;

    private WorkspacePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new WorkspacePolicy;
    }

    public function test_members_can_view_and_create_content(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Policy');
        $team->membershipRows()->create(['user_id' => $member->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);

        $this->assertTrue($this->policy->view($member, $team));
        $this->assertTrue($this->policy->createContent($member, $team));
        $this->assertFalse($this->policy->view($outsider, $team));
        $this->assertFalse($this->policy->createContent($outsider, $team));
    }

    public function test_only_team_owner_can_manage_team(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Kelola');
        $team->membershipRows()->create(['user_id' => $member->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);

        $this->assertTrue($this->policy->manageTeam($owner, $team));
        $this->assertFalse($this->policy->manageTeam($member, $team));
    }

    public function test_personal_workspace_cannot_be_managed_as_team(): void
    {
        $user = User::factory()->create();
        $personal = app(ProvisionPersonalWorkspace::class)->handle($user);

        $this->assertTrue($this->policy->view($user, $personal));
        $this->assertFalse($this->policy->manageTeam($user, $personal));
    }
}
