<?php

namespace Tests\Unit\Domain\Reminder\Actions;

use App\Domain\Category\Models\Category;
use App\Domain\Reminder\Actions\CreateManualReminder;
use App\Domain\Reminder\Actions\DeleteManualReminder;
use App\Domain\Reminder\Enums\ReminderKind;
use App\Domain\Todo\Actions\CreateTodo;
use App\Domain\Todo\Models\Todo;
use App\Domain\Workspace\Actions\ProvisionPersonalWorkspace;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeleteManualReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_manual_reminder_and_records_activity(): void
    {
        [$user, $todo] = $this->todoContext();
        $reminder = app(CreateManualReminder::class)->handle($todo, $user, now()->addDay());

        app(DeleteManualReminder::class)->handle($reminder, $user);

        $this->assertDatabaseMissing('todo_reminders', ['id' => $reminder->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'todo.reminder_deleted', 'actor_id' => $user->id]);
    }

    public function test_automatic_reminder_cannot_be_deleted_manually(): void
    {
        [$user, $todo] = $this->todoContext();
        $automatic = $todo->reminders()->where('kind', '!=', ReminderKind::Manual)->firstOrFail();

        try {
            app(DeleteManualReminder::class)->handle($automatic, $user);
            $this->fail('Expected a validation exception for an automatic reminder.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reminder', $exception->errors());
        }

        $this->assertDatabaseHas('todo_reminders', ['id' => $automatic->id]);
    }

    public function test_non_member_cannot_delete_reminder(): void
    {
        [$user, $todo] = $this->todoContext();
        $reminder = app(CreateManualReminder::class)->handle($todo, $user, now()->addDay());

        $this->expectException(AuthorizationException::class);
        app(DeleteManualReminder::class)->handle($reminder, User::factory()->create());
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
            'title' => 'Rapat evaluasi',
            'deadline_at' => now('Asia/Jakarta')->addDays(10)->format('Y-m-d H:i:s'),
        ]);

        return [$user, $todo];
    }
}
