<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Shift;
use Illuminate\Http\Request;

class RosterController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $weekStart = $request->get('week_start', now()->startOfWeek()->toDateString());

        $roster = EmployeeShift::with('employee', 'shift')
            ->whereBetween('date', [$weekStart, now()->parse($weekStart)->endOfWeek()->toDateString()])
            ->orderBy('date')
            ->orderBy('shift_id')
            ->get()
            ->groupBy(fn ($e) => $e->date->toDateString());

        $shifts = Shift::orderBy('start_time')->get();
        $employees = Employee::where('status', '!=', 'resigned')->with('department')->orderBy('name')->get();

        return view('erp.hrm.roster.index', compact('roster', 'shifts', 'employees', 'date', 'weekStart'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
            'date' => 'required|date',
            'is_off_day' => 'boolean',
        ]);
        EmployeeShift::updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'date' => $request->date,
            ],
            [
                'shift_id' => $request->shift_id,
                'is_off_day' => $request->boolean('is_off_day'),
            ]
        );
        return back()->with('success', 'Roster updated.');
    }

    public function destroy(EmployeeShift $employee_shift)
    {
        $employee_shift->delete();
        return back()->with('success', 'Roster entry removed.');
    }
}
