<?php

namespace Tests\Feature\Reminder;

use App\Domain\Category\Models\Category;
use App\Domain\Reminder\Actions\SendDueReminders;
use App\Domain\Reminder\Enums\ReminderStatus;
use App\Domain\Todo\Actions\CreateTodo;
use App\Domain\Workspace\Actions\CreateTeam;
use App\Domain\Workspace\Enums\WorkspaceRole;
use App\Models\User;
use App\Notifications\Todo\TodoReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReminderDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_team_reminder_is_sent_to_all_current_verified_members_once(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $unverified = User::factory()->unverified()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Reminder');
        foreach ([$member, $unverified] as $user) {
            $team->membershipRows()->create(['user_id' => $user->id, 'role' => WorkspaceRole::Member, 'joined_at' => now()]);
        }
        $category = Category::where('is_system', true)->firstOrFail();
        $todo = app(CreateTodo::class)->handle($team, $owner, $category, ['title' => 'Deadline tim', 'deadline_at' => now('Asia/Jakarta')->addDays(14)->format('Y-m-d H:i:s')]);
        $reminder = $todo->reminders()->first();
        $reminder->update(['scheduled_at' => now()->subMinute()]);

        $this->assertSame(1, app(SendDueReminders::class)->handle());
        $this->assertSame(0, app(SendDueReminders::class)->handle());
        Notification::assertSentTo([$owner, $member], TodoReminderNotification::class);
        Notification::assertNotSentTo($unverified, TodoReminderNotification::class);
        $this->assertDatabaseCount('reminder_deliveries', 2);
        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);
    }

    public function test_future_reminder_is_not_sent(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $team = app(CreateTeam::class)->handle($owner, 'Tim Future');
        $category = Category::where('is_system', true)->firstOrFail();
        app(CreateTodo::class)->handle($team, $owner, $category, ['title' => 'Belum dekat', 'deadline_at' => now('Asia/Jakarta')->addDays(14)->format('Y-m-d H:i:s')]);
        $this->assertSame(0, app(SendDueReminders::class)->handle());
        Notification::assertNothingSent();
    }
}
