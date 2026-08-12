<?php

namespace App\Domain\Workspace\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateTeamCapacity
{
    use AuthorizesDomainAction;

    public function __construct(private RecordActivity $activity) {}

    public function handle(Workspace $workspace, User $actor, int $limit): Workspace
    {
        $this->authorizeTeamOwner($workspace, $actor);
        if (! in_array($limit, [5, 10], true)) {
            throw ValidationException::withMessages(['member_limit' => 'Kapasitas tim hanya dapat diatur menjadi 5 atau 10 anggota.']);
        }
        if ($workspace->membershipRows()->count() > $limit) {
            throw ValidationException::withMessages(['member_limit' => 'Kapasitas tidak boleh lebih kecil dari jumlah anggota saat ini.']);
        }
        $old = $workspace->member_limit;
        $workspace->update(['member_limit' => $limit]);
        $this->activity->handle($workspace, $actor, 'team.capacity_changed', $workspace, null, ['member_limit' => ['old' => $old, 'new' => $limit]]);

        return $workspace;
    }
}
