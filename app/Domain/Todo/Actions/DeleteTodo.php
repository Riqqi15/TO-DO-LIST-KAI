<?php

namespace App\Domain\Todo\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteTodo
{
    use AuthorizesDomainAction;

    public function __construct(private RecordActivity $activity) {}

    public function handle(Todo $todo, User $actor): void
    {
        $this->authorizeAbility($actor, 'delete', $todo);
        DB::transaction(function () use ($todo, $actor) {
            $this->activity->handle($todo->workspace, $actor, 'todo.deleted', $todo, $todo->only(['id', 'title', 'description', 'status', 'deadline_at', 'category_id']));
            $todo->delete();
        });
    }
}
