<?php

namespace App\Http\Controllers;

use App\Models\ProductionSection;
use App\Models\Employee;
use Database\Seeders\ProductionSectionSeeder;
use Illuminate\Http\Request;

class ProductionSectionController extends Controller
{
    /**
     * Display root sections (Departments).
     */
    public function index()
    {
        $sections = ProductionSection::whereNull('parent_id')->withCount('employees')->orderBy('name')->get();

        return view('erp.sections.index', compact('sections'));
    }

    public function seedDefaults()
    {
        (new ProductionSectionSeeder())->run();

        return redirect()
            ->route('sections.index')
            ->with('success', 'Default production sections created (Cutting, Washing, Finishing, Sewing Lines).');
    }

    /**
     * Show a section.
     * If it's a container (like Sewing), show sub-lines.
     * If it's a leaf (like Cutting or a specific Line), show employee management.
     */
    public function show($id)
    {
        $section = ProductionSection::with('children')->findOrFail($id);
        
        // Logic: If it has children (Sub-Lines), show the List View for Children.
        // User explicitly wants to "Enter Sewing -> See Lines".
        // Also "Cutting" -> "See/Assign Employees".
        
        // We can use the 'children' count or type to decide.
        // Or simply: if children exist, show children list. If not, show employee manager.
        // But for "Sewing", we want to Add Lines.
        
        $isContainer = $section->slug == 'sewing' || $section->children->count() > 0;

        if ($isContainer) {
            $subSections = $section->children()->withCount('employees')->get();
            return view('erp.sections.show_children', compact('section', 'subSections'));
        } else {
            // Leaf node: Manage Employees
            return $this->manageEmployees($section);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:production_sections,id',
            'type' => 'required|in:department,line',
        ]);

        $section = ProductionSection::create([
            'name' => $request->name,
            'slug' => \Str::slug($request->name) . '-' . uniqid(), // Simple slug generation
            'type' => $request->type,
            'parent_id' => $request->parent_id,
        ]);

        return back()->with('success', 'Section/Line created successfully.');
    }

    /**
     * Internal method to show employee assignment view.
     */
    protected function manageEmployees($section)
    {
        // Get employees not assigned to any section OR assigned to THIS section
        $employees = Employee::whereNull('production_section_id')
                             ->orWhere('production_section_id', $section->id)
                             ->orderBy('name')
                             ->get();

        // Get list of potential supervisors (e.g., role 'line_supervisor' or all)
        // For flexibility, let's allow assigning anyone, or filter if requested.
        // User said "supervisor kenek", implying role.
        $supervisors = Employee::where('role', 'line_supervisor')->get();
        $stages = \App\Models\ProductionStage::orderBy('order_index')->get();
        return view('erp.sections.manage_employees', compact('section', 'employees', 'supervisors', 'stages'));
    }

    /**
     * Save employee assignments.
     */
    public function storeAssignments(Request $request, $id)
    {
        $section = ProductionSection::findOrFail($id);

        if ($request->filled('production_stage_id')) {
            $section->update(['production_stage_id' => $request->production_stage_id]);
        }
        if ($request->supervisor_id) {
            $section->update(['supervisor_id' => $request->supervisor_id]);
            Employee::where('id', $request->supervisor_id)->update(['role' => 'line_supervisor']);

            // Handle User Account & Email for Supervisor
            $supervisor = Employee::find($request->supervisor_id);
            if ($supervisor && $supervisor->email) {
                $password = \Str::random(8); // Always generate new password
                $user = null;

                if ($supervisor->user_id) {
                    $user = \App\Models\User::find($supervisor->user_id);
                } else {
                    $user = \App\Models\User::where('email', $supervisor->email)->first();
                }

                if ($user) {
                    // Update existing user with new password (Force Reset for Supervisor Access)
                    $user->update([
                        'password' => \Illuminate\Support\Facades\Hash::make($password),
                        // 'role' => 'line_supervisor' // Optional: Enforce role
                    ]);
                    if (!$supervisor->user_id) {
                        $supervisor->update(['user_id' => $user->id]);
                    }
                } else {
                    // Create New User
                    $user = \App\Models\User::create([
                        'name' => $supervisor->name,
                        'email' => $supervisor->email,
                        'password' => \Illuminate\Support\Facades\Hash::make($password),
                    ]);
                    $supervisor->update(['user_id' => $user->id]);
                }

                // Send Email with Valid Credentials
                try {
                    \Illuminate\Support\Facades\Mail::to($supervisor->email)->send(
                        new \App\Mail\SupervisorAssignmentEmail($supervisor, $section, $password)
                    );
                } catch (\Exception $e) {
                    \Log::error('Assignment email failed: ' . $e->getMessage());
                }
            }
        }

        // Reset all employees currently assigned to this section
        Employee::where('production_section_id', $id)->update(['production_section_id' => null]);

        // Assign selected employees
        $empIds = $request->input('employee_ids', []);
        
        \Log::info('Assigning Employees to Section ' . $id, ['ids' => $empIds, 'request_dump' => $request->all()]);

        if (!empty($empIds)) {
            Employee::whereIn('id', $empIds)->update(['production_section_id' => $id]);
        }

        return redirect()->back()->with('success', 'Team and Supervisor assigned successfully.');
    }
}
