<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDaily;
use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Show form to manually mark attendance for a selected date.
     */
    public function manualMark(Request $request)
    {
        $date = $request->filled('date') ? $request->date : now()->toDateString();
        $departmentId = $request->filled('department_id') ? $request->department_id : null;

        $employees = Employee::with('department')
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->orderBy('id')
            ->get(['id', 'name', 'department_id']);

        $existing = AttendanceDaily::where('date', $date)
            ->get()
            ->keyBy('employee_id');

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('erp.attendance.manual', compact('date', 'employees', 'existing', 'departments', 'departmentId'));
    }

    /**
     * Store manual attendance for the selected date.
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'rows' => 'required|array|min:1',
            'rows.*.employee_id' => 'required|exists:employees,id',
            'rows.*.check_in' => 'nullable|string',
            'rows.*.check_out' => 'nullable|string',
            'rows.*.status' => 'required|in:present,absent,late,leave',
        ]);

        $date = Carbon::parse($request->date)->toDateString();

        foreach ($request->rows as $row) {
            $firstIn = null;
            $lastOut = null;
            if (!empty($row['check_in'])) {
                $firstIn = Carbon::parse($date . ' ' . $row['check_in']);
            }
            if (!empty($row['check_out'])) {
                $lastOut = Carbon::parse($date . ' ' . $row['check_out']);
            }

            $workedMinutes = 0;
            if ($firstIn && $lastOut && $lastOut->gt($firstIn)) {
                $workedMinutes = (int) $firstIn->diffInMinutes($lastOut);
            }

            $payload = [
                'first_in' => $firstIn,
                'last_out' => $lastOut,
                'worked_minutes' => $workedMinutes,
                'late_minutes' => 0,
                'overtime_minutes' => 0,
                'status' => $row['status'],
                'source' => 'manual',
                'manually_corrected' => true,
            ];
            $existing = AttendanceDaily::query()
                ->where('employee_id', $row['employee_id'])
                ->whereDate('date', $date)
                ->first();
            if ($existing) {
                $existing->update($payload);
            } else {
                AttendanceDaily::create($payload + [
                    'employee_id' => $row['employee_id'],
                    'date' => $date,
                    'session_key' => '',
                ]);
            }
        }

        return redirect()
            ->route('attendance.index', ['date' => $request->date])
            ->with('success', 'Attendance marked successfully for ' . Carbon::parse($date)->format('d M Y') . '.');
    }

    public function index(Request $request)
    {
        $query = AttendanceDaily::with(['employee', 'employee.department'])
            ->orderBy('date', 'desc')
            ->orderBy('employee_id');

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->department_id));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->paginate(25)->withQueryString();
        $employees = Employee::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('erp.attendance.index', compact('attendances', 'employees', 'departments'));
    }
}
