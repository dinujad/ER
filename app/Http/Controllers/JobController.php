<?php

namespace App\Http\Controllers;

use App\Models\ManufacturingJob;
use App\Models\ManufacturingJobAdditionalCost;
use App\Models\Material;
use App\Models\MaterialIssue;
use App\Services\Manufacturing\JobWorkflowService;
use App\Support\Manufacturing\RecipeCatalog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JobController extends Controller
{
    public function __construct(
        private readonly JobWorkflowService $workflow
    ) {}

    public function index()
    {
        $jobs = ManufacturingJob::with(['packingBatches', 'hourlyProductions', 'lineAssignments', 'product'])
            ->orderByDesc('created_at')
            ->get();

        return view('erp.jobs.index', compact('jobs'));
    }

    public function create()
    {
        $materials = Material::where('type', 'fabric')->where('quantity', '>', 0)->get();

        return view('erp.jobs.create', compact('materials'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'style_ref' => 'required|string',
            'material_id' => 'required|exists:materials,id',
            'issued_quantity' => 'required|numeric|min:0.01',
            'accessories' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $material = Material::findOrFail($request->material_id);

        if ($material->quantity < $request->issued_quantity) {
            return back()->with('error', 'Insufficient fabric stock.');
        }

        DB::transaction(function () use ($request, $material) {
            $material->decrement('quantity', $request->issued_quantity);

            $issue = MaterialIssue::create([
                'material_id' => $material->id,
                'quantity_issued' => $request->issued_quantity,
                'issued_to' => 'Manufacturing Job',
            ]);

            $lastJob = ManufacturingJob::lockForUpdate()->orderByDesc('id')->first();
            $nextId = $lastJob ? $lastJob->id + 1 : 1;
            $jobNumber = 'JOB-' . date('Y') . '-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);

            $userId = auth()->id() ?? \App\Models\User::query()->value('id') ?? 1;

            $job = ManufacturingJob::create([
                'job_number' => $jobNumber,
                'material_issue_id' => $issue->id,
                'style_ref' => $request->style_ref,
                'issued_fabric_quantity' => $request->issued_quantity,
                'accessories' => $request->accessories,
                'status' => ManufacturingJob::STATUS_FABRIC_ISSUED,
                'issue_date' => Carbon::now(),
                'created_by' => $userId,
            ]);

            $issue->update(['manufacturing_job_id' => $job->id]);
        });

        return redirect()->route('jobs.index')->with('success', 'Fabric issued and job created.');
    }

    public function show($id)
    {
        $job = ManufacturingJob::with([
            'creator',
            'product',
            'materialIssue.material',
            'materialIssues.material',
            'lineAssignments',
            'washingTransfers.qcChecks',
            'hourlyProductions',
        ])->findOrFail($id);

        $recipeProducts = RecipeCatalog::productsWithRecipes();

        return view('erp.jobs.show', compact('job', 'recipeProducts'));
    }

    public function assignLineForm($id)
    {
        $job = ManufacturingJob::with('product')->findOrFail($id);

        try {
            $this->workflow->assertCanAssignLines($job);
        } catch (RuntimeException $e) {
            return redirect()->route('jobs.show', $job->id)->with('error', $e->getMessage());
        }

        $lines = \App\Models\ProductionSection::where('type', 'line')->get();

        return view('erp.manufacturing.assign_line', compact('job', 'lines'));
    }

    public function storeLineAssignment(Request $request, $id)
    {
        $request->validate([
            'lines' => 'required|array',
            'lines.*' => 'nullable|integer|min:0',
            'dispatch_date' => 'required|date',
            'materials' => 'nullable|array',
            'materials.*.issue_qty' => 'nullable|numeric|min:0',
        ]);

        $job = ManufacturingJob::findOrFail($id);

        try {
            $this->workflow->assignLines(
                $job,
                $request->lines,
                $request->dispatch_date,
                $request->input('materials', [])
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $totalAssigned = (int) array_sum($request->lines);
        $totalCut = (int) $job->total_cut_pieces;
        $message = 'Job assigned to lines successfully.';

        if ($totalAssigned < $totalCut) {
            $message .= ' (' . $totalAssigned . ' of ' . $totalCut . ' pcs on lines; ' . ($totalCut - $totalAssigned) . ' remaining.)';
        }

        return redirect()->route('jobs.show', $job->id)->with('success', $message);
    }

    public function sendToCutting(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $job = ManufacturingJob::findOrFail($id);

        try {
            $this->workflow->sendToCuttingWithRecipe($job, (int) $request->product_id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', 'Recipe selected and job sent to cutting.');
    }

    public function endJob($id)
    {
        $job = ManufacturingJob::findOrFail($id);

        if (!$job->canEnd()) {
            return back()->with('error', 'This job cannot be ended in its current state.');
        }

        $job->update(['status' => ManufacturingJob::STATUS_JOB_ENDED]);

        return back()->with('success', 'Job marked as ended. You can reopen manufacturing when needed.');
    }

    public function reopenJob(Request $request, $id)
    {
        $allowedStages = array_keys(ManufacturingJob::reopenStageOptions());

        $request->validate([
            'target_stage' => 'required|string|in:' . implode(',', $allowedStages),
            'clear_line_assignments' => 'nullable|boolean',
        ]);

        $job = ManufacturingJob::with('lineAssignments')->findOrFail($id);

        if (!$job->isEnded()) {
            return back()->with('error', 'Only ended jobs can be reopened.');
        }

        $target = $request->target_stage;

        if (in_array($target, [ManufacturingJob::STATUS_LINE_ASSIGNED, ManufacturingJob::STATUS_LINE_IN_PROGRESS], true)
            && $job->lineAssignments->isEmpty()) {
            return back()->with('error', 'Assign lines first, or reopen at after-cutting.');
        }

        if ($target === ManufacturingJob::STATUS_CUTTING_COMPLETED && !$job->hasCuttingData()) {
            return back()->with('error', 'Cannot reopen at after-cutting without cutting data.');
        }

        DB::transaction(function () use ($request, $job, $target) {
            if ($request->boolean('clear_line_assignments')) {
                $job->lineAssignments()->delete();
            }

            $job->update(['status' => $target]);
        });

        return redirect()
            ->route('jobs.show', $job->id)
            ->with('success', 'Manufacturing reopened at: ' . ManufacturingJob::statusLabel($target));
    }

    public function costs(ManufacturingJob $job)
    {
        $job->load(['additionalCosts.creator', 'materialIssues.material', 'packingBatches.materialIssues.material', 'materialIssue.material']);
        $breakdown = $job->costBreakdown();
        $categories = ManufacturingJobAdditionalCost::categoryOptions();

        return view('erp.jobs.costs', compact('job', 'breakdown', 'categories'));
    }

    public function storeCost(Request $request, ManufacturingJob $job)
    {
        $request->validate([
            'category' => 'required|string|in:' . implode(',', array_keys(ManufacturingJobAdditionalCost::categoryOptions())),
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
        ]);

        ManufacturingJobAdditionalCost::create([
            'manufacturing_job_id' => $job->id,
            'category' => $request->category,
            'description' => $request->description,
            'amount' => $request->amount,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('jobs.costs', $job->id)
            ->with('success', 'Production cost added.');
    }

    public function destroyCost(ManufacturingJob $job, ManufacturingJobAdditionalCost $cost)
    {
        if ((int) $cost->manufacturing_job_id !== (int) $job->id) {
            abort(404);
        }

        $cost->delete();

        return redirect()
            ->route('jobs.costs', $job->id)
            ->with('success', 'Cost entry removed.');
    }
}
