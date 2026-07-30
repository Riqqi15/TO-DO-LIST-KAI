<?php

namespace App\Domain\Workspace\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LeaveTeam
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(Workspace $workspace, User $user): void
    {
        if (! $workspace->isTeam() || ! $workspace->hasMember($user)) {
            throw ValidationException::withMessages(['team' => 'Anda bukan anggota tim ini.']);
        }
        if ($workspace->isOwner($user)) {
            throw ValidationException::withMessages(['team' => 'Pemilik harus memindahkan kepemilikan sebelum keluar dari tim.']);
        }
        $this->activity->handle($workspace, $user, 'team.member_left', $workspace, null, ['user_id' => $user->id, 'user_name' => $user->name]);
        $workspace->membershipRows()->where('user_id', $user->id)->delete();
    }
}
