<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MaterialIssue;
use App\Models\ManufacturingJob;
use App\Models\ProductRecipe;
use App\Models\ProductionSection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SupervisorController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
             return view('erp.supervisor.no_access', ['message' => 'No employee record linked to your account.']);
        }

        // Find the section where this employee is the SUPERVISOR
        $section = ProductionSection::where('supervisor_id', $employee->id)->first();

        if (!$section) {
            // Fallback: Check if they are just a worker assigned to a section?
            // User requested specific supervisor dashboard.
            return view('erp.supervisor.no_access', ['message' => 'You are not assigned as a Supervisor to any active Production Section.']);
        }
        
        $sectionSlug = $section->slug; 
        
        // Logic to filter jobs based on section slug/type
        $jobs = collect();

        if ($sectionSlug == 'cutting') {
             // Show jobs Sent to Cutting
             $jobs = ManufacturingJob::whereIn('status', [
                 ManufacturingJob::STATUS_SENT_TO_CUTTING,
                 ManufacturingJob::STATUS_CUTTING_IN_PROGRESS,
                 ManufacturingJob::STATUS_CUTTING_COMPLETED
             ])->orderBy('updated_at', 'desc')->get();

        } elseif ($sectionSlug == 'washing') {
             $jobs = ManufacturingJob::with('washingTransfers')
                 ->whereHas('washingTransfers', function ($q) {
                     $q->whereNull('parent_id')->whereIn('status', ['pending', 'received']);
                 })
                 ->orderBy('updated_at', 'desc')
                 ->get();

        } elseif ($sectionSlug == 'finishing') {
             // Finishing & Packing: Show jobs ready for QC or Packing
             // We look for jobs with washing transfers that are 'washing_completed' (Ready for QC)
             // or Jobs in Packing stage.
             $jobs = ManufacturingJob::with(['washingTransfers', 'packingBatches'])
                 ->whereHas('washingTransfers', function($q) {
                     $q->where('status', 'washing_completed')
                       ->orWhere('status', 'returned'); // returned means QC done, maybe in packing
                 })
                 ->orWhereHas('packingBatches', function($q){
                     $q->whereIn('status', ['packing', 'sent_to_final_check']);
                 })
                 ->orderBy('updated_at', 'desc')->get();

        } elseif ($section->type == 'line') {
             // Line (e.g. Line 01): assigned, in progress, or completed (ready for gate pass)
             $jobs = ManufacturingJob::with(['washingTransfers', 'hourlyProductions'])->whereHas('lineAssignments', function($q) use ($section) {
                 $q->where('line_name', $section->name);
             })->whereIn('status', [
                 ManufacturingJob::STATUS_LINE_ASSIGNED,
                 ManufacturingJob::STATUS_LINE_IN_PROGRESS,
                 ManufacturingJob::STATUS_LINE_COMPLETED,
             ])->orderBy('updated_at', 'desc')->get();
        } else {
             // Default / Other departments
             $jobs = ManufacturingJob::latest()->take(10)->get();
        }

        $sectionEmployees = $section->employees()->orderBy('name')->get();
        return view('erp.supervisor.dashboard', compact('section', 'jobs', 'employee', 'sectionEmployees'));
    }

    /**
     * Jobs list for supervisor: ongoing and completed with full details (e.g. cutting records).
     */
    public function jobs()
    {
        $user = Auth::user();
        $employee = $user->employee;
        if (!$employee) {
            return redirect()->route('supervisor.dashboard')->with('error', 'No employee record linked to your account.');
        }
        $section = ProductionSection::where('supervisor_id', $employee->id)->first();
        if (!$section) {
            return redirect()->route('supervisor.dashboard')->with('error', 'No section assigned.');
        }

        $sectionSlug = $section->slug;
        $ongoing = collect();
        $completed = collect();

        if ($sectionSlug === 'cutting') {
            $ongoing = ManufacturingJob::whereIn('status', [
                ManufacturingJob::STATUS_SENT_TO_CUTTING,
                ManufacturingJob::STATUS_CUTTING_IN_PROGRESS,
            ])->orderBy('updated_at', 'desc')->get();
            $completed = ManufacturingJob::where('status', ManufacturingJob::STATUS_CUTTING_COMPLETED)
                ->orderBy('updated_at', 'desc')->get();
        } else {
            $ongoing = ManufacturingJob::whereNotIn('status', [
                ManufacturingJob::STATUS_PACKING_COMPLETED,
                ManufacturingJob::STATUS_WAREHOUSE_RECEIVED,
                ManufacturingJob::STATUS_SOLD,
            ])->orderBy('updated_at', 'desc')->take(30)->get();
            $completed = ManufacturingJob::whereIn('status', [
                ManufacturingJob::STATUS_PACKING_COMPLETED,
                ManufacturingJob::STATUS_WAREHOUSE_RECEIVED,
                ManufacturingJob::STATUS_SOLD,
            ])->orderBy('updated_at', 'desc')->take(50)->get();
        }

        return view('erp.supervisor.jobs', compact('section', 'ongoing', 'completed', 'employee'));
    }

    public function sectionEmployees()
    {
        $user = Auth::user();
        $employee = $user->employee;
        if (!$employee) {
            return redirect()->route('supervisor.dashboard')->with('error', 'No employee record linked to your account.');
        }
        $section = ProductionSection::where('supervisor_id', $employee->id)->first();
        if (!$section) {
            return redirect()->route('supervisor.dashboard')->with('error', 'No section assigned.');
        }
        $employees = $section->employees()->orderBy('name')->get();
        return view('erp.supervisor.section_employees', compact('section', 'employees', 'employee'));
    }

    public function updateOwnPassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);
        return back()->with('success', 'Your password has been updated.');
    }

    public function updateEmployeePassword(Request $request, Employee $employee)
    {
        $user = Auth::user();
        $supervisorEmp = $user->employee;
        if (!$supervisorEmp) {
            abort(403, 'No employee record linked to your account.');
        }
        $section = ProductionSection::where('supervisor_id', $supervisorEmp->id)->first();
        if (!$section || !$section->employees()->where('employees.id', $employee->id)->exists()) {
            abort(403, 'You can only update passwords for employees in your section.');
        }
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $userAccount = $employee->user_id ? User::find($employee->user_id) : null;
        if (!$userAccount) {
            return back()->with('error', 'This employee does not have a login account. Create one from HR/Employees first.');
        }
        $userAccount->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password updated for ' . $employee->name . '.');
    }

    /**
     * Mark job as line production complete (line supervisor only). Then they can create washing gate pass.
     */
    public function markLineComplete(ManufacturingJob $job)
    {
        $user = Auth::user();
        $employee = $user->employee;
        if (!$employee) {
            return redirect()->route('supervisor.dashboard')->with('error', 'No employee record linked to your account.');
        }
        $section = ProductionSection::where('supervisor_id', $employee->id)->first();
        if (!$section || $section->type !== 'line') {
            return redirect()->route('supervisor.dashboard')->with('error', 'Only line supervisors can mark line complete.');
        }
        $assignedToThisLine = $job->lineAssignments()->where('line_name', $section->name)->exists();
        if (!$assignedToThisLine) {
            abort(403, 'This job is not assigned to your line.');
        }
        if (!in_array($job->status, [ManufacturingJob::STATUS_LINE_ASSIGNED, ManufacturingJob::STATUS_LINE_IN_PROGRESS], true)) {
            return back()->with('error', 'Job must be on line (assigned or in progress) to mark complete.');
        }
        $job->update(['status' => ManufacturingJob::STATUS_LINE_COMPLETED]);
        return back()->with('success', 'Line production marked complete. Send pieces to Washing Stock next.');
    }

    /**
     * Record ingredient usage for a job from this section (recipe ingredients for this section's stage).
     */
    public function jobRecordUsage(ManufacturingJob $job)
    {
        $user = Auth::user();
        $employee = $user->employee;
        if (!$employee) {
            return redirect()->route('supervisor.dashboard')->with('error', 'No employee record linked to your account.');
        }
        $section = ProductionSection::where('supervisor_id', $employee->id)->first();
        if (!$section || !$section->production_stage_id) {
            return redirect()->route('supervisor.dashboard')->with('error', 'Your section is not linked to a production stage. Contact admin.');
        }
        $allowedJobIds = $this->getSectionJobIds($section);
        if (!$allowedJobIds->contains($job->id)) {
            abort(403, 'This job is not in your section.');
        }
        $recipe = ProductRecipe::where('product_id', $job->product_id)->with(['steps' => function ($q) use ($section) {
            $q->where('production_stage_id', $section->production_stage_id)->orderBy('order_index');
        }, 'steps.ingredients.material'])->first();
        if (!$recipe || $recipe->steps->isEmpty()) {
            return redirect()->route('supervisor.dashboard')->with('error', 'No recipe or no steps for your section\'s stage for this job.');
        }
        $existingIssues = MaterialIssue::where('manufacturing_job_id', $job->id)
            ->where('production_section_id', $section->id)
            ->get()
            ->keyBy(function ($i) {
                return $i->recipe_step_id . '_' . $i->material_id;
            });
        return view('erp.supervisor.record_usage', compact('job', 'section', 'recipe', 'existingIssues'));
    }

    public function storeJobUsage(Request $request, ManufacturingJob $job)
    {
        $user = Auth::user();
        $employee = $user->employee;
        if (!$employee) {
            return redirect()->route('supervisor.dashboard')->with('error', 'No employee record linked to your account.');
        }
        $section = ProductionSection::where('supervisor_id', $employee->id)->first();
        if (!$section || !$section->production_stage_id) {
            return redirect()->route('supervisor.dashboard')->with('error', 'Your section is not linked to a production stage.');
        }
        $allowedJobIds = $this->getSectionJobIds($section);
        if (!$allowedJobIds->contains($job->id)) {
            abort(403, 'This job is not in your section.');
        }
        $recipe = ProductRecipe::where('product_id', $job->product_id)->with(['steps' => function ($q) use ($section) {
            $q->where('production_stage_id', $section->production_stage_id)->with('ingredients');
        }])->first();
        if (!$recipe) {
            return back()->with('error', 'No recipe for this job.');
        }
        $request->validate([
            'usage' => 'required|array',
            'usage.*.recipe_step_id' => 'required|exists:recipe_steps,id',
            'usage.*.material_id' => 'required|exists:materials,id',
            'usage.*.quantity_used' => 'nullable|numeric|min:0',
        ]);
        foreach ($request->usage as $row) {
            $qty = (float) $row['quantity_used'];
            $stepId = (int) $row['recipe_step_id'];
            $materialId = (int) $row['material_id'];
            $issue = MaterialIssue::where('manufacturing_job_id', $job->id)
                ->where('production_section_id', $section->id)
                ->where('recipe_step_id', $stepId)
                ->where('material_id', $materialId)
                ->first();
            if ($qty > 0) {
                if ($issue) {
                    $issue->update(['quantity_issued' => $qty]);
                } else {
                    MaterialIssue::create([
                        'manufacturing_job_id' => $job->id,
                        'production_section_id' => $section->id,
                        'recipe_step_id' => $stepId,
                        'material_id' => $materialId,
                        'quantity_issued' => $qty,
                        'issued_to' => $section->name,
                    ]);
                }
            } elseif ($issue) {
                $issue->delete();
            }
        }
        return redirect()->route('supervisor.dashboard')->with('success', 'Ingredient usage recorded for ' . $job->job_number . '.');
    }

    protected function getSectionJobIds(ProductionSection $section): \Illuminate\Support\Collection
    {
        $sectionSlug = $section->slug;
        if ($sectionSlug === 'cutting') {
            return ManufacturingJob::whereIn('status', [
                ManufacturingJob::STATUS_SENT_TO_CUTTING,
                ManufacturingJob::STATUS_CUTTING_IN_PROGRESS,
                ManufacturingJob::STATUS_CUTTING_COMPLETED,
            ])->pluck('id');
        }
        if ($sectionSlug === 'washing') {
            return ManufacturingJob::whereHas('washingTransfers', function ($q) {
                $q->whereNull('parent_id')->whereIn('status', ['pending', 'received']);
            })->pluck('id');
        }
        if ($sectionSlug === 'finishing') {
            return ManufacturingJob::whereHas('washingTransfers', fn ($q) => $q->whereIn('status', ['washing_completed', 'returned']))
                ->orWhereHas('packingBatches', fn ($q) => $q->whereIn('status', ['packing', 'sent_to_final_check']))
                ->pluck('id');
        }
        if ($section->type === 'line') {
            return ManufacturingJob::whereHas('lineAssignments', fn ($q) => $q->where('line_name', $section->name))
                ->whereIn('status', [ManufacturingJob::STATUS_LINE_ASSIGNED, ManufacturingJob::STATUS_LINE_IN_PROGRESS, ManufacturingJob::STATUS_LINE_COMPLETED])
                ->pluck('id');
        }
        return ManufacturingJob::latest()->take(50)->pluck('id');
    }
}
