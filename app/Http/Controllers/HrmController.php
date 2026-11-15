<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AttendanceDaily;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HrmController extends Controller
{
    /**
     * HRM dashboard – Ultimate POS style: KPIs, attendance, leave, payroll, announcements.
     */
    public function index()
    {
        $today = now()->toDateString();
        $attendanceDate = $today;
        if (! AttendanceDaily::query()->whereDate('date', $today)->exists()) {
            $latest = AttendanceDaily::query()->max('date');
            if ($latest) {
                $attendanceDate = Carbon::parse($latest)->toDateString();
            }
        }
        $attendanceOn = Carbon::parse($attendanceDate);
        $totalEmployees = Employee::where('status', '!=', 'resigned')->count();
        $todayRecords = AttendanceDaily::with('employee')
            ->whereDate('date', $attendanceDate)
            ->get()
            ->groupBy('employee_id');
        $presentToday = $todayRecords->filter(function ($rows) {
            return $rows->contains(fn ($row) => in_array($row->status, ['present', 'late'], true) || $row->first_in);
        })->count();
        $lateToday = $todayRecords->filter(fn ($rows) => $rows->contains(fn ($row) => $row->status === 'late'))->count();
        $absentToday = $todayRecords->filter(function ($rows) {
            $came = $rows->contains(fn ($row) => in_array($row->status, ['present', 'late'], true) || $row->first_in);

            return ! $came && $rows->contains(fn ($row) => $row->status === 'absent');
        })->count();
        $todayRows = $todayRecords->map(function ($rows) {
            return $rows->sortByDesc(fn ($row) => $row->first_in ? $row->first_in->getTimestamp() : 0)->first();
        })->filter()->values();

        $pendingLeaveCount = LeaveRequest::where('status', LeaveRequest::STATUS_PENDING)->count();
        $onLeaveToday = LeaveRequest::where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        $lastPayrollRun = PayrollRun::with('creator')->latest()->first();
        $announcements = Announcement::active()->latest()->limit(5)->get();

        $departmentsCount = Department::count();
        $designationsCount = Designation::count();
        $shiftsCount = Shift::count();

        return view('erp.hrm.index', compact(
            'totalEmployees',
            'presentToday',
            'absentToday',
            'lateToday',
            'todayRecords',
            'todayRows',
            'pendingLeaveCount',
            'onLeaveToday',
            'lastPayrollRun',
            'announcements',
            'departmentsCount',
            'designationsCount',
            'shiftsCount',
            'attendanceDate',
            'attendanceOn'
        ));
    }
}
