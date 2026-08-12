<?php

namespace App\Domain\Workspace\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferTeamOwnership
{
    use AuthorizesDomainAction;

    public function __construct(private RecordActivity $activity) {}

    public function handle(Workspace $workspace, User $owner, User $newOwner): void
    {
        $this->authorizeTeamOwner($workspace, $owner);
        if ($owner->is($newOwner) || ! $workspace->hasMember($newOwner)) {
            throw ValidationException::withMessages(['user_id' => 'Pemilik baru harus anggota lain dalam tim.']);
        }
        DB::transaction(function () use ($workspace, $owner, $newOwner) {
            $workspace->membershipRows()->where('user_id', $owner->id)->update(['role' => WorkspaceRole::Member->value]);
            $workspace->membershipRows()->where('user_id', $newOwner->id)->update(['role' => WorkspaceRole::Owner->value]);
            $workspace->update(['created_by' => $newOwner->id]);
            $this->activity->handle($workspace, $owner, 'team.ownership_transferred', $workspace, null, ['from_user_id' => $owner->id, 'to_user_id' => $newOwner->id]);
        });
    }
}
