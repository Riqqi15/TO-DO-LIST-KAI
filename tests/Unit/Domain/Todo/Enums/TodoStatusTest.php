<?php

namespace Tests\Unit\Domain\Todo\Enums;

use App\Domain\Todo\Enums\TodoStatus;
use PHPUnit\Framework\TestCase;

class TodoStatusTest extends TestCase
{
    public function test_each_case_has_expected_value(): void
    {
        $this->assertSame('belum_dikerjakan', TodoStatus::BelumDikerjakan->value);
        $this->assertSame('sedang_dikerjakan', TodoStatus::SedangDikerjakan->value);
        $this->assertSame('selesai', TodoStatus::Selesai->value);
        $this->assertCount(3, TodoStatus::cases());
    }

    public function test_label_returns_human_readable_indonesian_text(): void
    {
        $this->assertSame('Belum Dikerjakan', TodoStatus::BelumDikerjakan->label());
        $this->assertSame('Sedang Dikerjakan', TodoStatus::SedangDikerjakan->label());
        $this->assertSame('Selesai', TodoStatus::Selesai->label());
    }

    public function test_can_be_resolved_from_stored_value(): void
    {
        $this->assertSame(TodoStatus::SedangDikerjakan, TodoStatus::from('sedang_dikerjakan'));
        $this->assertNull(TodoStatus::tryFrom('tidak_ada'));
    }
}
