<?php

namespace App\Domain\Workspace\Actions;

use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Domain\Workspace\Enums\WorkspaceType;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProvisionPersonalWorkspace
{
    public function handle(User $user): Workspace
    {
        return DB::transaction(function () use ($user) {
            $workspace = Workspace::query()->firstOrCreate(
                ['created_by' => $user->id, 'type' => WorkspaceType::Personal->value],
                ['name' => 'Ruang Pribadi '.$user->name, 'member_limit' => 1],
            );

            $workspace->membershipRows()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => WorkspaceRole::Owner->value, 'joined_at' => now()],
            );

            return $workspace;
        });
    }
}
