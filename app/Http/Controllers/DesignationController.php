<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index()
    {
        $designations = Designation::with('department')->withCount('employees')->orderBy('title')->paginate(15);
        return view('erp.hrm.designations.index', compact('designations'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('erp.hrm.designations.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'level' => 'nullable|integer|min:1',
        ]);
        Designation::create($request->only('title', 'department_id', 'level'));
        return redirect()->route('designations.index')->with('success', 'Designation created.');
    }

    public function edit(Designation $designation)
    {
        $departments = Department::orderBy('name')->get();
        return view('erp.hrm.designations.edit', compact('designation', 'departments'));
    }

    public function update(Request $request, Designation $designation)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'level' => 'nullable|integer|min:1',
        ]);
        $designation->update($request->only('title', 'department_id', 'level'));
        return redirect()->route('designations.index')->with('success', 'Designation updated.');
    }

    public function destroy(Designation $designation)
    {
        if ($designation->employees()->count() > 0) {
            return redirect()->route('designations.index')->with('error', 'Cannot delete designation with employees.');
        }
        $designation->delete();
        return redirect()->route('designations.index')->with('success', 'Designation deleted.');
    }
}
