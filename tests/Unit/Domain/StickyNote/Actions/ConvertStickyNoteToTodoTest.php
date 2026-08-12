<?php

namespace Tests\Unit\Domain\StickyNote\Actions;

use App\Domain\Category\Models\Category;
use App\Domain\StickyNote\Actions\ConvertStickyNoteToTodo;
use App\Domain\StickyNote\Actions\CreateStickyNote;
use App\Domain\StickyNote\Models\StickyNote;
use App\Domain\Todo\Enums\TodoStatus;
use App\Domain\Workspace\Actions\CreateTeam;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConvertStickyNoteToTodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_note_content_becomes_todo_description_when_none_given(): void
    {
        [$owner, $member, $note] = $this->noteContext();

        $todo = app(ConvertStickyNoteToTodo::class)->handle($note, $member, $this->systemCategory(), [
            'title' => 'Tindak lanjut ide',
            'deadline_at' => now('Asia/Jakarta')->addDays(8)->format('Y-m-d H:i:s'),
        ]);

        $this->assertSame($note->content, $todo->description);
        $this->assertSame(TodoStatus::BelumDikerjakan, $todo->status);
        $this->assertSame($note->workspace_id, $todo->workspace_id);
        $this->assertSame($member->id, $todo->created_by);

        $note->refresh();
        $this->assertSame($todo->id, $note->converted_to_todo_id);
        $this->assertNotNull($note->converted_at);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'sticky_note.converted_to_todo',
            'actor_id' => $member->id,
            'workspace_id' => $note->workspace_id,
        ]);
        $this->assertNotSame($owner->id, $todo->created_by);
    }

    public function test_explicit_description_is_kept(): void
    {
        [, $member, $note] = $this->noteContext();

        $todo = app(ConvertStickyNoteToTodo::class)->handle($note, $member, $this->systemCategory(), [
            'title' => 'Tindak lanjut ide',
            'description' => 'Deskripsi khusus',
            'deadline_at' => now('Asia/Jakarta')->addDays(8)->format('Y-m-d H:i:s'),
        ]);

        $this->assertSame('Deskripsi khusus', $todo->description);
    }

    public function test_note_cannot_be_converted_twice(): void
    {
        [, $member, $note] = $this->noteContext();
        $data = [
            'title' => 'Tindak lanjut ide',
            'deadline_at' => now('Asia/Jakarta')->addDays(8)->format('Y-m-d H:i:s'),
        ];
        app(ConvertStickyNoteToTodo::class)->handle($note, $member, $this->systemCategory(), $data);

        try {
            app(ConvertStickyNoteToTodo::class)->handle($note->fresh(), $member, $this->systemCategory(), $data);
            $this->fail('Expected a validation exception for an already converted note.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('note', $exception->errors());
        }

        $this->assertDatabaseCount('todos', 1);
    }

    public function test_non_member_cannot_convert_note(): void
    {
        [, , $note] = $this->noteContext();

        $this->expectException(AuthorizationException::class);
        app(ConvertStickyNoteToTodo::class)->handle($note, User::factory()->create(), $this->systemCategory(), [
            'title' => 'Tindak lanjut ide',
            'deadline_at' => now('Asia/Jakarta')->addDays(8)->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_failed_conversion_rolls_back_the_whole_transaction(): void
    {
        [, $member, $note] = $this->noteContext();

        try {
            app(ConvertStickyNoteToTodo::class)->handle($note, $member, $this->systemCategory(), [
                'title' => 'Deadline terlalu dekat',
                'deadline_at' => now('Asia/Jakarta')->addMinute()->format('Y-m-d H:i:s'),
            ]);
            $this->fail('Expected a validation exception for the deadline.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('deadline_at', $exception->errors());
        }

        $this->assertDatabaseCount('todos', 0);
        $this->assertNull($note->fresh()->converted_to_todo_id);
    }

    private function systemCategory(): Category
    {
        return Category::where('is_system', true)->firstOrFail();
    }

    /**
     * @return array{0: User, 1: User, 2: StickyNote}
     */
    private function noteContext(): array
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Brainstorm');
        $team->membershipRows()->create(['user_id' => $member->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);
        $note = app(CreateStickyNote::class)->handle($team, $owner, ['content' => 'Ide kampanye baru']);

        return [$owner, $member, $note];
    }
}
