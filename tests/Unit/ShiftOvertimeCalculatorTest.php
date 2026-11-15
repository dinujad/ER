<?php

namespace Tests\Unit;

use App\Models\AttendanceDaily;
use App\Models\Shift;
use App\Services\ShiftOvertimeCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ShiftOvertimeCalculatorTest extends TestCase
{
    public function test_overtime_is_time_worked_after_shift_end_times_the_shift_rate(): void
    {
        $shift = new Shift([
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_overnight' => false,
            'ot_grace_mins' => 0,
            'ot_rate_per_hour' => 500,
        ]);
        $row = new AttendanceDaily([
            'status' => 'present',
            'worked_minutes' => 690,
        ]);
        $row->first_in = Carbon::parse('2026-09-01 08:00:00');
        $row->last_out = Carbon::parse('2026-09-01 19:30:00');

        $result = (new ShiftOvertimeCalculator())->forDay('2026-09-01', new Collection([$row]), $shift);

        $this->assertSame(150, $result['ot_minutes']);
        $this->assertSame(1250.0, $result['ot_amount']);
        $this->assertSame('present', $result['status']);
    }

    public function test_grace_minutes_delay_when_overtime_starts(): void
    {
        $shift = new Shift([
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_overnight' => false,
            'ot_grace_mins' => 30,
            'ot_rate_per_hour' => 500,
        ]);
        $row = new AttendanceDaily(['status' => 'present']);
        $row->first_in = Carbon::parse('2026-09-01 08:00:00');
        $row->last_out = Carbon::parse('2026-09-01 19:30:00');

        $result = (new ShiftOvertimeCalculator())->forDay('2026-09-01', new Collection([$row]), $shift);

        $this->assertSame(120, $result['ot_minutes']);
        $this->assertSame(1000.0, $result['ot_amount']);
    }

    public function test_no_clock_in_is_absent_and_has_no_overtime(): void
    {
        $shift = new Shift([
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'ot_rate_per_hour' => 500,
        ]);
        $row = new AttendanceDaily(['status' => 'absent', 'worked_minutes' => 0]);

        $result = (new ShiftOvertimeCalculator())->forDay('2026-09-01', new Collection([$row]), $shift);

        $this->assertSame('absent', $result['status']);
        $this->assertSame(0, $result['ot_minutes']);
        $this->assertSame(0.0, $result['ot_amount']);
    }
}
