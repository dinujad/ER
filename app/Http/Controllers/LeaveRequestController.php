<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDaily;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\ProductionLine;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['employee', 'leaveType', 'approver'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }
        if ($request->filled('from_date')) {
            $query->where('end_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('start_date', '<=', $request->to_date);
        }

        $leaveRequests = $query->paginate(20)->withQueryString();
        $employees = Employee::orderBy('name')->get(['id', 'name']);
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('erp.hrm.leave_requests.index', compact('leaveRequests', 'employees', 'leaveTypes'));
    }

    public function create()
    {
        $employees = Employee::orderBy('name')->get(['id', 'name']);
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();
        return view('erp.hrm.leave_requests.create', compact('employees', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'duration_unit' => 'required|in:full,half,hourly',
            'hours' => 'nullable|numeric|min:0.5|max:12',
            'reason' => 'nullable|string|max:1000',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $type = LeaveType::findOrFail($request->leave_type_id);
        $start = Carbon::parse($request->start_date)->startOfDay();
        $end = Carbon::parse($request->end_date)->startOfDay();
        $span = (int) $start->diffInDays($end) + 1;
        $unit = $request->duration_unit;
        if ($unit === 'half') {
            $days = 0.5;
        } elseif ($unit === 'hourly') {
            $days = round(((float) $request->input('hours', 0)) / 8, 2);
        } else {
            $days = $span;
        }
        $balance = $this->balanceFor((int) $request->employee_id, $type, (int) $start->year);
        if ($type->days_per_year > 0 && $days > $balance) {
            return back()->withInput()->with('error', 'Leave balance is '.$balance.' day(s). This request is '.$days.'.');
        }

        $path = $request->hasFile('document') ? $request->file('document')->store('leave-documents', 'public') : null;
        LeaveRequest::create([
            'employee_id' => $request->employee_id,
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days' => $days,
            'duration_unit' => $unit,
            'hours' => $unit === 'hourly' ? $request->hours : null,
            'reason' => $request->reason,
            'document_path' => $path,
            'status' => LeaveRequest::STATUS_PENDING,
            'supervisor_status' => 'pending',
            'hr_status' => 'pending',
        ]);

        return redirect()->route('leave-requests.index')->with('success', 'Leave request submitted.');
    }

    public function supervisorApprove(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING || $leaveRequest->supervisor_status === 'approved') {
            return back()->with('error', 'Supervisor step is already done.');
        }
        $leaveRequest->update(['supervisor_status' => 'approved']);

        return back()->with('success', 'Supervisor approved. Waiting for HR.');
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return back()->with('error', 'Request already processed.');
        }
        if ($leaveRequest->supervisor_status !== 'approved') {
            return back()->with('error', 'Supervisor must approve before HR.');
        }
        $leaveRequest->update([
            'status' => LeaveRequest::STATUS_APPROVED,
            'hr_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
        $this->syncAttendance($leaveRequest);

        return back()->with('success', 'HR approved. Attendance marked as leave.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return back()->with('error', 'Request already processed.');
        }
        $stage = $leaveRequest->supervisor_status === 'approved' ? 'hr_status' : 'supervisor_status';
        $leaveRequest->update([
            $stage => 'rejected',
            'status' => LeaveRequest::STATUS_REJECTED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);
        return back()->with('success', 'Leave rejected.');
    }

    public function cancel(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status === LeaveRequest::STATUS_CANCELLED) {
            return back()->with('error', 'Already cancelled.');
        }
        $leaveRequest->update(['status' => LeaveRequest::STATUS_CANCELLED]);

        return back()->with('success', 'Leave cancelled.');
    }

    public function balances(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $employees = Employee::with('department')->orderBy('name')->get();
        $types = LeaveType::where('is_active', true)->orderBy('name')->get();
        $rows = $employees->map(function (Employee $employee) use ($types, $year) {
            return [
                'employee' => $employee,
                'balances' => $types->map(fn (LeaveType $type) => [
                    'type' => $type,
                    'balance' => $this->balanceFor($employee->id, $type, $year),
                ]),
            ];
        });

        return view('erp.hrm.leave_requests.balances', compact('rows', 'types', 'year'));
    }

    public function calendar(Request $request)
    {
        $month = Carbon::parse(($request->input('month') ?: now()->format('Y-m')).'-01')->startOfMonth();
        $query = LeaveRequest::with(['employee.department', 'employee.productionLine', 'leaveType'])
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $month->copy()->endOfMonth())
            ->whereDate('end_date', '>=', $month->copy()->startOfMonth());
        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->department_id));
        }
        if ($request->filled('production_line_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('production_line_id', $request->production_line_id));
        }
        $leaves = $query->orderBy('start_date')->get();
        $departments = Department::orderBy('name')->get();
        $lines = ProductionLine::orderBy('name')->get();

        return view('erp.hrm.leave_requests.calendar', compact('leaves', 'month', 'departments', 'lines'));
    }

    public function balanceFor(int $employeeId, LeaveType $type, int $year): float
    {
        if ((int) $type->days_per_year === 0) {
            return 999;
        }
        $used = (float) LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $type->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $year)
            ->sum('days');

        return max(0, (float) $type->days_per_year + (float) $type->carry_forward_days - $used);
    }

    private function syncAttendance(LeaveRequest $leaveRequest): void
    {
        $date = $leaveRequest->start_date->copy()->startOfDay();
        $end = $leaveRequest->end_date->copy()->startOfDay();
        while ($date->lte($end)) {
            $existing = AttendanceDaily::query()
                ->where('employee_id', $leaveRequest->employee_id)
                ->whereDate('date', $date->toDateString())
                ->get();
            $locked = $existing->contains(fn ($row) => $row->is_finalized || $row->manually_corrected || $row->first_in);
            if (! $locked) {
                AttendanceDaily::updateOrCreate(
                    [
                        'employee_id' => $leaveRequest->employee_id,
                        'date' => $date->toDateString(),
                        'session_key' => 'leave',
                    ],
                    [
                        'status' => 'leave',
                        'source' => 'leave',
                        'manually_corrected' => false,
                        'is_finalized' => false,
                    ]
                );
            }
            $date->addDay();
        }
    }
}
