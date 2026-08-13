<?php

namespace Tests\Feature\Reminder;

use App\Domain\Category\Models\Category;
use App\Domain\Reminder\Enums\ReminderKind;
use App\Domain\Todo\Actions\CreateTodo;
use App\Domain\Todo\Models\Todo;
use App\Domain\Workspace\Actions\ProvisionPersonalWorkspace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualReminderRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_reminder_is_stored_from_wib_input(): void
    {
        [$user, $todo] = $this->todoContext();
        $scheduledWib = now('Asia/Jakarta')->addDays(2)->startOfMinute();

        $this->actingAs($user)->post(route('reminders.store', $todo), [
            'scheduled_at' => $scheduledWib->format('Y-m-d H:i:s'),
        ])->assertSessionHasNoErrors();

        $reminder = $todo->reminders()->where('kind', ReminderKind::Manual)->firstOrFail();
        $this->assertTrue($scheduledWib->utc()->equalTo($reminder->scheduled_at));
    }

    public function test_scheduled_at_is_required_and_must_be_a_date(): void
    {
        [$user, $todo] = $this->todoContext();

        $this->actingAs($user)->post(route('reminders.store', $todo), [])->assertSessionHasErrors('scheduled_at');
        $this->actingAs($user)->post(route('reminders.store', $todo), ['scheduled_at' => 'bukan tanggal'])
            ->assertSessionHasErrors('scheduled_at');
        $this->assertDatabaseMissing('todo_reminders', ['kind' => ReminderKind::Manual->value]);
    }

    public function test_manual_reminder_can_be_deleted_by_member(): void
    {
        [$user, $todo] = $this->todoContext();
        $this->actingAs($user)->post(route('reminders.store', $todo), [
            'scheduled_at' => now('Asia/Jakarta')->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertSessionHasNoErrors();
        $reminder = $todo->reminders()->where('kind', ReminderKind::Manual)->firstOrFail();

        $this->actingAs($user)->delete(route('reminders.destroy', $reminder))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('todo_reminders', ['id' => $reminder->id]);
    }

    public function test_outsider_cannot_create_reminder(): void
    {
        [, $todo] = $this->todoContext();

        $this->actingAs(User::factory()->create())->post(route('reminders.store', $todo), [
            'scheduled_at' => now('Asia/Jakarta')->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertForbidden();
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
            'title' => 'Susun proposal',
            'deadline_at' => now('Asia/Jakarta')->addDays(10)->format('Y-m-d H:i:s'),
        ]);

        return [$user, $todo];
    }
}
