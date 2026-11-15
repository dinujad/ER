<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index()
    {
        $leaveTypes = LeaveType::orderBy('name')->paginate(15);
        return view('erp.hrm.leave_types.index', compact('leaveTypes'));
    }

    public function create()
    {
        return view('erp.hrm.leave_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20|unique:leave_types,code',
            'days_per_year' => 'nullable|integer|min:0',
            'carry_forward_days' => 'nullable|integer|min:0',
            'is_paid' => 'nullable|boolean',
            'requires_approval' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);
        LeaveType::create([
            'name' => $request->name,
            'code' => $request->code,
            'days_per_year' => $request->input('days_per_year', 0),
            'carry_forward_days' => $request->input('carry_forward_days', 0),
            'is_paid' => $request->boolean('is_paid'),
            'requires_approval' => $request->boolean('requires_approval'),
            'is_active' => true,
            'description' => $request->description,
        ]);
        return redirect()->route('leave-types.index')->with('success', 'Leave type created.');
    }

    public function edit(LeaveType $leaveType)
    {
        return view('erp.hrm.leave_types.edit', compact('leaveType'));
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20|unique:leave_types,code,' . $leaveType->id,
            'days_per_year' => 'nullable|integer|min:0',
            'carry_forward_days' => 'nullable|integer|min:0',
            'is_paid' => 'nullable|boolean',
            'requires_approval' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);
        $leaveType->update([
            'name' => $request->name,
            'code' => $request->code,
            'days_per_year' => $request->input('days_per_year', 0),
            'carry_forward_days' => $request->input('carry_forward_days', 0),
            'is_paid' => $request->boolean('is_paid'),
            'requires_approval' => $request->boolean('requires_approval'),
            'is_active' => $request->boolean('is_active'),
            'description' => $request->description,
        ]);
        return redirect()->route('leave-types.index')->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType)
    {
        if ($leaveType->leaveRequests()->count() > 0) {
            return redirect()->route('leave-types.index')->with('error', 'Cannot delete: leave requests exist.');
        }
        $leaveType->delete();
        return redirect()->route('leave-types.index')->with('success', 'Leave type deleted.');
    }
}
