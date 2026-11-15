<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDaily;
use App\Models\Employee;
use App\Models\OtRequest;
use App\Services\ShiftOvertimeCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OtRequestController extends Controller
{
    public function index()
    {
        $requests = OtRequest::with(['employee.department', 'employee.defaultShift', 'approver'])
            ->orderByDesc('work_date')
            ->paginate(20);
        $calculator = app(ShiftOvertimeCalculator::class);
        $requests->getCollection()->transform(function (OtRequest $row) use ($calculator) {
            $punches = AttendanceDaily::query()
                ->where('employee_id', $row->employee_id)
                ->whereDate('date', $row->work_date)
                ->get();
            $shift = $row->employee?->defaultShift;
            $calc = $calculator->forDay($row->work_date->toDateString(), $punches, $shift);
            $row->actual_ot_minutes = $calc['ot_minutes'];
            $limit = (float) ($shift->ot_daily_limit_hours ?? 4);
            $row->over_limit = (float) $row->planned_hours > $limit;

            return $row;
        });

        return view('erp.hrm.ot.index', compact('requests'));
    }

    public function create()
    {
        $employees = Employee::with('department', 'defaultShift')->orderBy('name')->get();

        return view('erp.hrm.ot.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'work_date' => 'required|date',
            'category' => 'required|in:workday,weekend,holiday',
            'planned_hours' => 'required|numeric|min:0.5|max:16',
            'reason' => 'nullable|string|max:500',
        ]);
        $employee = Employee::with('defaultShift')->findOrFail($request->employee_id);
        $shift = $employee->defaultShift;
        $rate = match ($request->category) {
            'weekend' => (float) ($shift->weekend_ot_rate_per_hour ?? 0),
            'holiday' => (float) ($shift->holiday_ot_rate_per_hour ?? 0),
            default => (float) ($shift->ot_rate_per_hour ?? 0),
        };
        if ($rate <= 0 && $shift) {
            $rate = (float) $shift->ot_rate_per_hour;
        }
        $limit = (float) ($shift->ot_daily_limit_hours ?? 4);
        OtRequest::create([
            'employee_id' => $employee->id,
            'work_date' => $request->work_date,
            'category' => $request->category,
            'planned_hours' => $request->planned_hours,
            'rate_per_hour' => $rate,
            'reason' => $request->reason,
            'status' => 'pending',
            'supervisor_status' => 'pending',
            'hr_status' => 'pending',
        ]);
        $message = 'OT request submitted.';
        if ((float) $request->planned_hours > $limit) {
            $message .= ' Planned hours are above the '.$limit.'h shift limit.';
        }

        return redirect()->route('ot-requests.index')->with('success', $message);
    }

    public function supervisorApprove(OtRequest $otRequest)
    {
        if ($otRequest->status !== 'pending' || $otRequest->supervisor_status === 'approved') {
            return back()->with('error', 'Supervisor step is already done.');
        }
        $otRequest->update(['supervisor_status' => 'approved']);

        return back()->with('success', 'Supervisor approved. Waiting for HR.');
    }

    public function approve(OtRequest $otRequest, ShiftOvertimeCalculator $calculator)
    {
        if ($otRequest->status !== 'pending') {
            return back()->with('error', 'Request already processed.');
        }
        if ($otRequest->supervisor_status !== 'approved') {
            return back()->with('error', 'Supervisor must approve before HR.');
        }
        $employee = $otRequest->employee()->with('defaultShift')->first();
        $punches = AttendanceDaily::query()
            ->where('employee_id', $otRequest->employee_id)
            ->whereDate('date', $otRequest->work_date)
            ->get();
        $actual = $calculator->forDay($otRequest->work_date->toDateString(), $punches, $employee?->defaultShift);
        $hours = (float) $otRequest->planned_hours;
        $otRequest->update([
            'status' => 'approved',
            'hr_status' => 'approved',
            'approved_hours' => $hours,
            'actual_minutes' => $actual['ot_minutes'],
            'amount' => round($hours * (float) $otRequest->rate_per_hour, 2),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'HR approved OT. It will be included in payroll.');
    }

    public function reject(Request $request, OtRequest $otRequest)
    {
        if ($otRequest->status !== 'pending') {
            return back()->with('error', 'Request already processed.');
        }
        $stage = $otRequest->supervisor_status === 'approved' ? 'hr_status' : 'supervisor_status';
        $otRequest->update([
            'status' => 'rejected',
            $stage => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'OT request rejected.');
    }
}
