<?php

namespace Tests\Unit\Notifications\Todo;

use App\Domain\Category\Models\Category;
use App\Domain\Todo\Actions\CreateTodo;
use App\Domain\Todo\Models\Todo;
use App\Domain\Workspace\Actions\ProvisionPersonalWorkspace;
use App\Models\User;
use App\Notifications\Todo\TodoReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoReminderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_delivered_over_mail_channel(): void
    {
        [, $todo] = $this->todoContext();

        $this->assertSame(['mail'], (new TodoReminderNotification($todo))->via($todo->creator));
    }

    public function test_mail_message_contains_title_and_deadline_in_wib(): void
    {
        [$user, $todo] = $this->todoContext();

        $mail = (new TodoReminderNotification($todo))->toMail($user);

        $this->assertSame('Reminder task: '.$todo->title, $mail->subject);
        $this->assertSame('Halo '.$user->name.',', $mail->greeting);
        $this->assertSame(route('todo.index'), $mail->actionUrl);
        $this->assertContains($todo->title, $mail->introLines);
        $this->assertContains(
            'Deadline: '.$todo->deadline_at->copy()->timezone('Asia/Jakarta')->format('d M Y H:i').' WIB',
            $mail->introLines,
        );
    }

    public function test_array_payload_exposes_todo_identity(): void
    {
        [$user, $todo] = $this->todoContext();

        $this->assertSame([
            'todo_id' => $todo->id,
            'title' => $todo->title,
            'deadline_at' => $todo->deadline_at->toIso8601String(),
        ], (new TodoReminderNotification($todo))->toArray($user));
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
            'title' => 'Kirim laporan bulanan',
            'deadline_at' => now('Asia/Jakarta')->addDays(10)->format('Y-m-d H:i:s'),
        ]);

        return [$user, $todo];
    }
}
