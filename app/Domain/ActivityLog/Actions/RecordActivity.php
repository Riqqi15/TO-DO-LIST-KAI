<?php

namespace App\Domain\ActivityLog\Actions;

use App\Domain\ActivityLog\Models\ActivityLog;
use App\Domain\Workspace\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordActivity
{
    public function handle(?Workspace $workspace, ?User $actor, string $action, Model $subject, ?array $snapshot = null, ?array $changes = null): ActivityLog
    {
        return ActivityLog::create([
            'workspace_id' => $workspace?->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'snapshot' => $snapshot,
            'changes' => $changes,
        ]);
    }
}
