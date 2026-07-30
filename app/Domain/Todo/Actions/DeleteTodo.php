<?php

namespace App\Domain\Todo\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class DeleteTodo
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(Todo $todo, User $actor): void
    {
        if (! $actor->can('delete', $todo)) {
            throw new AuthorizationException;
        }
        DB::transaction(function () use ($todo, $actor) {
            $this->activity->handle($todo->workspace, $actor, 'todo.deleted', $todo, $todo->only(['id', 'title', 'description', 'status', 'deadline_at', 'category_id']));
            $todo->delete();
        });
    }
}
