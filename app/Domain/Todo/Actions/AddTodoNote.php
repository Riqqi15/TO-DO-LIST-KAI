<?php

namespace App\Domain\Todo\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\Todo\Models\Todo;
use App\Domain\Todo\Models\TodoNote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AddTodoNote
{
    use AuthorizesDomainAction;

    public function __construct(private RecordActivity $activity) {}

    public function handle(Todo $todo, User $actor, string $body): TodoNote
    {
        $this->authorizeAbility($actor, 'update', $todo);

        return DB::transaction(function () use ($todo, $actor, $body) {
            $note = $todo->notes()->create([
                'created_by' => $actor->id,
                'body' => $body,
            ]);

            $this->activity->handle($todo->workspace, $actor, 'todo.note_added', $todo, null, ['note_id' => $note->id, 'body' => $body]);

            return $note->load('creator');
        });
    }
}
