<?php

namespace Tests\Unit\Jobs;

use App\Domain\Reminder\Actions\SendDueReminders;
use App\Jobs\ProcessDueReminders;
use Mockery;
use Tests\TestCase;

class ProcessDueRemindersTest extends TestCase
{
    public function test_handle_delegates_to_send_due_reminders_action(): void
    {
        $action = Mockery::mock(SendDueReminders::class);
        $action->shouldReceive('handle')->once()->andReturn(2);

        (new ProcessDueReminders)->handle($action);
    }

    public function test_job_is_retried_and_time_limited(): void
    {
        $job = new ProcessDueReminders;

        $this->assertSame(3, $job->tries);
        $this->assertSame(120, $job->timeout);
    }
}
