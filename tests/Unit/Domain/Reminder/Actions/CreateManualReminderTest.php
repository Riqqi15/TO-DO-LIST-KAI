<?php

namespace Tests\Unit\Domain\Reminder\Actions;

use App\Domain\Category\Models\Category;
use App\Domain\Reminder\Actions\CreateManualReminder;
use App\Domain\Reminder\Enums\ReminderKind;
use App\Domain\Reminder\Enums\ReminderStatus;
use App\Domain\Todo\Actions\CreateTodo;
use App\Domain\Todo\Enums\TodoStatus;
use App\Domain\Todo\Models\Todo;
use App\Domain\Workspace\Actions\ProvisionPersonalWorkspace;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateManualReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_scheduled_manual_reminder_and_records_activity(): void
    {
        [$user, $todo] = $this->todoContext();

        $reminder = app(CreateManualReminder::class)->handle($todo, $user, now()->addDay());

        $this->assertSame(ReminderKind::Manual, $reminder->kind);
        $this->assertSame(ReminderStatus::Scheduled, $reminder->status);
        $this->assertSame($todo->id, $reminder->todo_id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'todo.reminder_created', 'actor_id' => $user->id]);
    }

    public function test_non_member_cannot_create_reminder(): void
    {
        [, $todo] = $this->todoContext();
        $outsider = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        app(CreateManualReminder::class)->handle($todo, $outsider, now()->addDay());
    }

    public function test_completed_todo_cannot_receive_reminder(): void
    {
        [$user, $todo] = $this->todoContext();
        $todo->update(['status' => TodoStatus::Selesai, 'completed_at' => now()]);

        $this->assertValidationFails(fn () => app(CreateManualReminder::class)->handle($todo->fresh(), $user, now()->addDay()));
    }

    public function test_reminder_must_be_in_the_future_and_before_deadline(): void
    {
        [$user, $todo] = $this->todoContext();

        $this->assertValidationFails(fn () => app(CreateManualReminder::class)->handle($todo, $user, now()->subMinute()));
        $this->assertValidationFails(fn () => app(CreateManualReminder::class)->handle($todo, $user, $todo->deadline_at->copy()->addDay()));
        $this->assertDatabaseMissing('todo_reminders', ['kind' => ReminderKind::Manual->value]);
    }

    private function assertValidationFails(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a validation exception for scheduled_at.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('scheduled_at', $exception->errors());
        }
    }

    /**
     * @return array{0: User, 1: Todo}
     */
    private function todoContext(): array
    {
        $user = User::factory()->create();
        $workspace = app(ProvisionPersonalWorkspace::class)->handle($user);
        $category = Category::where('is_system', true)->firstOrFail();
        $todo = app(CreateTodo::class)->handle($workspace, $user, $category, [
            'title' => 'Siapkan materi',
            'deadline_at' => now('Asia/Jakarta')->addDays(10)->format('Y-m-d H:i:s'),
        ]);

        return [$user, $todo];
    }
}
