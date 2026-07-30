<?php

namespace App\Domain\Workspace\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Domain\Workspace\Models\TeamInvite;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JoinTeam
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(User $user, string $code): Workspace
    {
        return DB::transaction(function () use ($user, $code) {
            $invite = TeamInvite::query()->where('token_hash', hash('sha256', strtoupper($code)))->lockForUpdate()->first();
            if (! $invite) {
                throw ValidationException::withMessages(['code' => 'Kode tim tidak valid.']);
            }
            if ($invite->revoked_at || $invite->expires_at->isPast()) {
                throw ValidationException::withMessages(['code' => 'Kode tim sudah kedaluwarsa.']);
            }

            $workspace = Workspace::query()->lockForUpdate()->findOrFail($invite->workspace_id);
            if ($workspace->hasMember($user)) {
                throw ValidationException::withMessages(['code' => 'Anda sudah menjadi anggota tim ini.']);
            }
            if ($workspace->membershipRows()->count() >= $workspace->member_limit) {
                throw ValidationException::withMessages(['code' => 'Kapasitas anggota tim sudah penuh.']);
            }

            $workspace->membershipRows()->create(['user_id' => $user->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);
            $this->activity->handle($workspace, $user, 'team.member_joined', $workspace, ['user_id' => $user->id, 'user_name' => $user->name]);

            return $workspace;
        });
    }
}
