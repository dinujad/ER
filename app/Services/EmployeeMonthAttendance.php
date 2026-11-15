<?php

namespace App\Services;

use App\Models\AttendanceDaily;
use App\Models\Employee;
use App\Models\EmployeeShift;
use Carbon\Carbon;

class EmployeeMonthAttendance
{
    public function __construct(private ShiftOvertimeCalculator $overtime)
    {
    }

    /**
     * One row per attendance date for this employee. Present, absent, and OT
     * all come from the same attendance rows and the employee's shift.
     *
     * @return array{days: array<int, array<string, mixed>>, present: int, absent: int, late: int, worked_minutes: int, ot_minutes: int, ot_amount: float}
     */
    public function summarize(Employee $employee, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();
        $employee->loadMissing('defaultShift');

        $rows = AttendanceDaily::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get()
            ->groupBy(fn ($row) => $row->date->toDateString());

        $roster = EmployeeShift::with('shift')
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn ($row) => $row->date->toDateString());

        $days = [];
        $present = 0;
        $absent = 0;
        $late = 0;
        $worked = 0;
        $otMinutes = 0;
        $otAmount = 0.0;

        foreach ($rows as $date => $dayRows) {
            $assignment = $roster->get($date);
            $offDay = (bool) ($assignment?->is_off_day);
            $shift = $offDay ? null : ($assignment?->shift ?: $employee->defaultShift);
            $calc = $this->overtime->forDay($date, $dayRows, $shift, $offDay);

            if (in_array($calc['status'], ['present', 'late'], true)) {
                $present++;
            }
            if ($calc['status'] === 'late') {
                $late++;
            }
            if ($calc['status'] === 'absent') {
                $absent++;
            }
            $worked += $calc['worked_minutes'];
            $otMinutes += $calc['ot_minutes'];
            $otAmount += $calc['ot_amount'];

            $days[] = [
                'date' => $date,
                'shift_name' => $offDay ? 'Off day' : ($shift?->name ?? null),
                'off_day' => $offDay,
                'status' => $calc['status'],
                'first_in' => $calc['first_in'],
                'last_out' => $calc['last_out'],
                'worked_minutes' => $calc['worked_minutes'],
                'ot_minutes' => $calc['ot_minutes'],
                'ot_amount' => $calc['ot_amount'],
            ];
        }

        return [
            'days' => $days,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'worked_minutes' => $worked,
            'ot_minutes' => $otMinutes,
            'ot_amount' => round($otAmount, 2),
        ];
    }
}
