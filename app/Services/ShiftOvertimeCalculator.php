<?php

namespace App\Services;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ShiftOvertimeCalculator
{
    /**
     * OT is punched time after shift end plus the shift's grace.
     * Pay is those hours times the rate saved on the shift.
     *
     * @param  Collection<int, \App\Models\AttendanceDaily>  $dayRows
     * @return array{worked_minutes: int, ot_minutes: int, ot_amount: float, first_in: ?Carbon, last_out: ?Carbon, status: string}
     */
    public function forDay(string $date, Collection $dayRows, ?Shift $shift, bool $offDay = false): array
    {
        $firstIn = $dayRows->filter(fn ($row) => $row->first_in)->sortBy(fn ($row) => $row->first_in->getTimestamp())->first()?->first_in;
        $lastOut = $dayRows->filter(fn ($row) => $row->last_out)->sortByDesc(fn ($row) => $row->last_out->getTimestamp())->first()?->last_out;
        $came = $dayRows->contains(fn ($row) => $row->first_in || in_array($row->status, ['present', 'late'], true));

        $status = 'absent';
        if ($came) {
            $status = $dayRows->contains(fn ($row) => $row->status === 'late') ? 'late' : 'present';
        } elseif ($dayRows->contains(fn ($row) => $row->status === 'leave')) {
            $status = 'leave';
        } elseif ($dayRows->contains(fn ($row) => $row->status === 'incomplete')) {
            $status = 'incomplete';
        } elseif ($dayRows->contains(fn ($row) => $row->status === 'absent')) {
            $status = 'absent';
        }

        $worked = 0;
        foreach ($dayRows as $row) {
            if ($row->first_in && $row->last_out && $row->last_out->greaterThan($row->first_in)) {
                $worked += (int) floor(($row->last_out->getTimestamp() - $row->first_in->getTimestamp()) / 60);
            } elseif ((int) $row->worked_minutes > 0) {
                $worked += (int) $row->worked_minutes;
            }
        }

        $otMinutes = 0;
        $otAmount = 0.0;
        if (! $offDay && $shift && $came) {
            $otStart = $this->overtimeStartsAt($date, $shift);
            $seconds = 0;
            foreach ($dayRows as $row) {
                if (! $row->first_in || ! $row->last_out || $row->last_out->lessThanOrEqualTo($otStart)) {
                    continue;
                }
                $from = $row->first_in->greaterThan($otStart) ? $row->first_in : $otStart;
                $seconds += max(0, $row->last_out->getTimestamp() - $from->getTimestamp());
            }
            $otMinutes = (int) floor($seconds / 60);
            $rate = (float) ($shift->ot_rate_per_hour ?? 0);
            $otAmount = round(($otMinutes / 60) * $rate, 2);
        }

        return [
            'worked_minutes' => $worked,
            'ot_minutes' => $otMinutes,
            'ot_amount' => $otAmount,
            'first_in' => $firstIn,
            'last_out' => $lastOut,
            'status' => $status,
        ];
    }

    public function overtimeStartsAt(string $date, Shift $shift): Carbon
    {
        $day = Carbon::parse($date)->startOfDay();
        $start = $day->copy()->setTimeFromTimeString($this->clock($shift->start_time));
        $end = $day->copy()->setTimeFromTimeString($this->clock($shift->end_time));
        if ($shift->is_overnight || $end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return $end->addMinutes((int) ($shift->ot_grace_mins ?? 0));
    }

    private function clock(mixed $time): string
    {
        return Carbon::parse($time)->format('H:i:s');
    }
}
