<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeSalaryController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['department', 'designation'])->orderBy('name')->get();
        $salaries = [];
        foreach ($employees as $emp) {
            $salaries[$emp->id] = EmployeeSalary::currentForEmployee($emp->id);
        }
        return view('erp.hrm.salary.employees.index', compact('employees', 'salaries'));
    }

    public function create(Request $request)
    {
        $employeeId = $request->get('employee_id');
        $employees = Employee::orderBy('name')->get(['id', 'name']);
        $employee = $employeeId ? Employee::find($employeeId) : null;
        return view('erp.hrm.salary.employees.create', compact('employees', 'employee'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'basic_salary' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
        ]);
        $from = Carbon::parse($request->effective_from);
        EmployeeSalary::where('employee_id', $request->employee_id)
            ->whereNull('effective_to')
            ->where('effective_from', '<', $from->toDateString())
            ->update(['effective_to' => $from->copy()->subDay()->toDateString()]);
        EmployeeSalary::create([
            'employee_id' => $request->employee_id,
            'basic_salary' => $request->basic_salary,
            'effective_from' => $request->effective_from,
        ]);
        return redirect()->route('employee-salaries.index')->with('success', 'Salary updated.');
    }
}
