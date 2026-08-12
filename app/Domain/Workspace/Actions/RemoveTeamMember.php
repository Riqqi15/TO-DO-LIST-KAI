<?php

namespace App\Domain\Workspace\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RemoveTeamMember
{
    use AuthorizesDomainAction;

    public function __construct(private RecordActivity $activity) {}

    public function handle(Workspace $workspace, User $owner, User $member): void
    {
        $this->authorizeTeamOwner($workspace, $owner);
        if ($owner->is($member)) {
            throw ValidationException::withMessages(['user_id' => 'Pemilik harus memindahkan kepemilikan sebelum keluar.']);
        }
        if (! $workspace->hasMember($member)) {
            throw ValidationException::withMessages(['user_id' => 'Pengguna bukan anggota tim.']);
        }
        $workspace->membershipRows()->where('user_id', $member->id)->delete();
        $this->activity->handle($workspace, $owner, 'team.member_removed', $workspace, null, ['user_id' => $member->id, 'user_name' => $member->name]);
    }
}
