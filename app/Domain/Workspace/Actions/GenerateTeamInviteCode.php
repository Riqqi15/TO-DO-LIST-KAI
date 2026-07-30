<?php

namespace App\Domain\Workspace\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Workspace\Models\TeamInvite;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateTeamInviteCode
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(Workspace $workspace, User $actor): array
    {
        if (! $workspace->isTeam() || ! $workspace->isOwner($actor)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($workspace, $actor) {
            $workspace->invites()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            do {
                $code = Str::upper(Str::random(8));
            } while (TeamInvite::query()->where('token_hash', hash('sha256', $code))->exists());
            $invite = $workspace->invites()->create(['created_by' => $actor->id, 'token_hash' => hash('sha256', $code), 'expires_at' => now()->addMinutes(5)]);
            $this->activity->handle($workspace, $actor, 'team.invite_generated', $invite, ['expires_at' => $invite->expires_at->toIso8601String()]);

            return ['code' => $code, 'expires_at' => $invite->expires_at];
        });
    }
}
