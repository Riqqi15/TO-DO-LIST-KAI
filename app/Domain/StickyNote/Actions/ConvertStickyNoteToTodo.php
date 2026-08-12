<?php

namespace App\Domain\StickyNote\Actions;

use App\Domain\ActivityLog\Actions\RecordActivity;
use App\Domain\Category\Models\Category;
use App\Domain\Shared\Concerns\AuthorizesDomainAction;
use App\Domain\StickyNote\Models\StickyNote;
use App\Domain\Todo\Actions\CreateTodo;
use App\Domain\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertStickyNoteToTodo
{
    use AuthorizesDomainAction;

    public function __construct(private CreateTodo $createTodo, private RecordActivity $activity) {}

    public function handle(StickyNote $note, User $actor, Category $category, array $data, array $manualReminders = []): Todo
    {
        $this->authorizeAbility($actor, 'update', $note);
        if ($note->converted_to_todo_id) {
            throw ValidationException::withMessages(['note' => 'Catatan ini sudah pernah dijadikan task.']);
        }

        return DB::transaction(function () use ($note, $actor, $category, $data, $manualReminders) {
            $data['description'] = $data['description'] ?? $note->content;
            $todo = $this->createTodo->handle($note->workspace, $actor, $category, $data, $manualReminders);
            $note->update(['converted_to_todo_id' => $todo->id, 'converted_at' => now()]);
            $this->activity->handle($note->workspace, $actor, 'sticky_note.converted_to_todo', $note, null, ['todo_id' => $todo->id, 'todo_title' => $todo->title]);

            return $todo;
        });
    }
}
