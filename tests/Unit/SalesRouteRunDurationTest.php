<?php

namespace Tests\Unit;

use App\Models\SalesRouteRun;
use Carbon\Carbon;
use Tests\TestCase;

class SalesRouteRunDurationTest extends TestCase
{
    public function test_duration_label_uses_integer_seconds(): void
    {
        $run = new SalesRouteRun();
        $run->started_at = Carbon::parse('2026-08-16 08:00:00');
        $run->completed_at = Carbon::parse('2026-08-16 10:05:30');

        $this->assertSame(7530, $run->durationSeconds());
        $this->assertSame('2h 5m', $run->durationLabel());
    }

    public function test_duration_is_zero_without_start(): void
    {
        $run = new SalesRouteRun();

        $this->assertSame(0, $run->durationSeconds());
        $this->assertSame('0m', $run->durationLabel());
        $this->assertSame('—', $run->startedLabel());
    }
}
