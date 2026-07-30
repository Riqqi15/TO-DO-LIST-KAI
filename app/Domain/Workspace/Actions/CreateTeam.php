<?php

namespace App\Domain\Workspace\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Domain\Workspace\Enums\WorkspaceType;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTeam
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(User $owner, string $name): Workspace
    {
        return DB::transaction(function () use ($owner, $name) {
            $workspace = Workspace::create(['created_by' => $owner->id, 'name' => $name, 'type' => WorkspaceType::Team, 'member_limit' => 5]);
            $workspace->membershipRows()->create(['user_id' => $owner->id, 'role' => WorkspaceRole::Owner, 'joined_at' => now()]);
            $this->activity->handle($workspace, $owner, 'team.created', $workspace, $workspace->only(['id', 'name', 'member_limit']));

            return $workspace;
        });
    }
}
