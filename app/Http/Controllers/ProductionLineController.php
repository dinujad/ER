<?php

namespace App\Http\Controllers;

use App\Models\ProductionLine;
use App\Models\Employee;
use Illuminate\Http\Request;

class ProductionLineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lines = ProductionLine::withCount('employees')->get();
        return view('erp.lines.index', compact('lines'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('erp.lines.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:production_lines,name',
            'description' => 'nullable|string',
        ]);

        ProductionLine::create($request->all());

        return redirect()->route('lines.index')->with('success', 'Production Line Created Successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductionLine $productionLine) // Implicit binding
    {
        // $productionLine instance is injected but routes are resource 'lines' -> 'line'
        // If route binding is standard, it expects {line}. 
        // Let's rely on standard id to be safe or use explicit binding if needed.
        return view('erp.lines.edit', ['line' => $productionLine]);
    }
    
    // Custom edit method to avoid binding confusion depending on Laravel version/route naming
    public function editLine($id) {
        $line = ProductionLine::findOrFail($id);
        return view('erp.lines.edit', compact('line'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:production_lines,name,'.$id,
            'description' => 'nullable|string',
        ]);

        $line = ProductionLine::findOrFail($id);
        $line->update($request->all());

        return redirect()->route('lines.index')->with('success', 'Production Line Updated Successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $line = ProductionLine::findOrFail($id);
        $line->delete();
        return redirect()->route('lines.index')->with('success', 'Production Line Deleted.');
    }

    /**
     * Show form to assign employees to this line.
     */
    public function assignEmployeesForm($id)
    {
        $line = ProductionLine::with('employees')->findOrFail($id);
        // Get employees not assigned to any line OR assigned to THIS line
        $employees = Employee::whereNull('production_line_id')
                             ->orWhere('production_line_id', $id)
                             ->orderBy('name')
                             ->get();
                             
        return view('erp.lines.assign_employees', compact('line', 'employees'));
    }

    /**
     * Update employee assignments for this line.
     */
    public function assignEmployeesStore(Request $request, $id)
    {
        $line = ProductionLine::findOrFail($id);
        
        // Reset all employees currently assigned to this line
        Employee::where('production_line_id', $id)->update(['production_line_id' => null]);

        // Assign selected employees
        if ($request->has('employee_ids')) {
            Employee::whereIn('id', $request->employee_ids)->update(['production_line_id' => $id]);
        }

        return redirect()->route('lines.index')->with('success', 'Employees assigned to line successfully.');
    }
}
