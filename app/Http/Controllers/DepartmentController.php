<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('employees')->orderBy('name')->paginate(15);
        return view('erp.hrm.departments.index', compact('departments'));
    }

    public function create()
    {
        return view('erp.hrm.departments.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255', 'code' => 'nullable|string|max:50|unique:departments,code']);
        Department::create($request->only('name', 'code'));
        return redirect()->route('departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department)
    {
        return view('erp.hrm.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $request->validate(['name' => 'required|string|max:255', 'code' => 'nullable|string|max:50|unique:departments,code,' . $department->id]);
        $department->update($request->only('name', 'code'));
        return redirect()->route('departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        if ($department->employees()->count() > 0) {
            return redirect()->route('departments.index')->with('error', 'Cannot delete department with employees.');
        }
        $department->delete();
        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }
}
