<?php

namespace App\Http\Controllers;

use App\Models\SalaryComponent;
use Illuminate\Http\Request;

class SalaryComponentController extends Controller
{
    public function index()
    {
        $components = SalaryComponent::orderBy('sort_order')->orderBy('type')->orderBy('name')->paginate(20);
        return view('erp.hrm.salary.components.index', compact('components'));
    }

    public function create()
    {
        return view('erp.hrm.salary.components.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:salary_components,code',
            'type' => 'required|in:allowance,deduction',
            'calculation_type' => 'required|in:fixed,percentage',
            'default_value' => 'required|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        SalaryComponent::create($request->only('name', 'code', 'type', 'calculation_type', 'default_value', 'sort_order') + ['is_active' => true]);
        return redirect()->route('salary-components.index')->with('success', 'Salary component created.');
    }

    public function edit(SalaryComponent $salaryComponent)
    {
        return view('erp.hrm.salary.components.edit', compact('salaryComponent'));
    }

    public function update(Request $request, SalaryComponent $salaryComponent)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:salary_components,code,' . $salaryComponent->id,
            'type' => 'required|in:allowance,deduction',
            'calculation_type' => 'required|in:fixed,percentage',
            'default_value' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $salaryComponent->update($request->only('name', 'code', 'type', 'calculation_type', 'default_value', 'is_active', 'sort_order'));
        return redirect()->route('salary-components.index')->with('success', 'Salary component updated.');
    }

    public function destroy(SalaryComponent $salaryComponent)
    {
        $salaryComponent->delete();
        return redirect()->route('salary-components.index')->with('success', 'Salary component deleted.');
    }
}
