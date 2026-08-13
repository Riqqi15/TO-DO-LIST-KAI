<?php

namespace App\Domain\Todo\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Todo\Models\TodoNote;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class DeleteTodoNote
{
    public function __construct(private RecordActivity $activity) {}

    public function handle(TodoNote $note, User $actor): void
    {
        $todo = $note->todo;
        if ($note->created_by !== $actor->id && ! $todo->workspace->isOwner($actor)) {
            throw new AuthorizationException;
        }

        DB::transaction(function () use ($note, $todo, $actor) {
            $note->delete();
            $this->activity->handle($todo->workspace, $actor, 'todo.note_deleted', $todo, null, ['note_id' => $note->id, 'body' => $note->body]);
        });
    }
}
