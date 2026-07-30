<?php

namespace App\Domain\Workspace\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteTeam
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(Workspace $workspace, User $owner, string $confirmation): void
    {
        if (! $workspace->isTeam() || ! $workspace->isOwner($owner)) {
            throw new AuthorizationException;
        }
        if ($confirmation !== 'konfirmasi hapus tim '.$workspace->name) {
            throw ValidationException::withMessages(['confirmation' => 'Teks konfirmasi tidak sesuai.']);
        }
        DB::transaction(function () use ($workspace, $owner) {
            $snapshot = $workspace->only(['id', 'name', 'type', 'member_limit']);
            $this->activity->handle($workspace, $owner, 'team.deleted', $workspace, $snapshot);
            $workspace->delete();
        });
    }
}
