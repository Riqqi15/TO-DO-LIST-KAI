<?php

namespace Tests\Unit\Support;

use App\Domain\Todo\Support\TodoDeadline;
use App\Support\Wib;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WibTest extends TestCase
{
    public function test_input_is_interpreted_as_wib_and_stored_as_utc(): void
    {
        $moment = Wib::toUtc('2026-08-12 09:30');

        $this->assertSame('UTC', $moment->timezone->getName());
        $this->assertSame('2026-08-12 02:30', $moment->format('Y-m-d H:i'));
    }

    public function test_formatting_renders_utc_values_in_wib(): void
    {
        $moment = Carbon::parse('2026-08-12 02:30', 'UTC');

        $this->assertSame('2026-08-12 09:30', Wib::format($moment));
        $this->assertSame('2026-08-12', Wib::formatDate($moment));
        $this->assertSame('12 Aug 2026 09:30', Wib::format($moment, 'd M Y H:i'));
    }

    public function test_deadline_lead_time_guard_rejects_near_deadlines(): void
    {
        TodoDeadline::assertLeadTime(now()->addHour());

        $this->expectException(ValidationException::class);
        TodoDeadline::assertLeadTime(now()->addMinutes(TodoDeadline::MIN_LEAD_MINUTES - 1));
    }
}
