<?php

namespace App\Http\Controllers;

use App\Models\HourlyProduction;
use App\Models\HourlyProductionDailyTarget;
use App\Models\ManufacturingJob;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HourlyProductionController extends Controller
{
    public function create(Request $request)
    {
        $jobs = ManufacturingJob::whereIn('status', ManufacturingJob::hourlyEntryStatuses())
            ->orderBy('job_number')
            ->get();

        $selectedJobId    = $request->query('job_id');
        $selectedLine     = $request->query('line_name');
        $assignedLines    = collect();
        $job              = null;
        $hourTotalsByLine = [];
        $hourTotals       = [];
        $totalProduced    = 0;
        $allowedDates     = [];
        $lineStats        = [];
        $needsTargetSetup = false;
        $jobStartDate     = null;

        $today = now()->toDateString();
        $selectedDate = $request->query('production_date', $today);

        if ($selectedJobId) {
            $job = ManufacturingJob::with(['lineAssignments', 'hourlyProductions', 'washingTransfers'])
                ->find($selectedJobId);

            if ($job) {
                $allowedDates = $job->allowedProductionDates();
                $jobStartDate = $job->productionStartDate()->toDateString();

                if (!in_array($selectedDate, $allowedDates, true)) {
                    $selectedDate = in_array($today, $allowedDates, true)
                        ? $today
                        : ($allowedDates[array_key_last($allowedDates)] ?? $today);
                }

                $assignedLines = $job->lineAssignments
                    ->pluck('line_name')
                    ->unique()
                    ->values();

                if ($assignedLines->isNotEmpty()) {
                    if (!$selectedLine || !$assignedLines->contains($selectedLine)) {
                        $selectedLine = $assignedLines->first();
                    }
                }

                $needsTargetSetup = $assignedLines->isNotEmpty()
                    && !$job->hasDailyTargetsFor($selectedDate);

                foreach ($assignedLines as $line) {
                    $assignment = $job->lineAssignments->firstWhere('line_name', $line);
                    $jobTarget = (int) ($assignment->assigned_quantity ?? 0);
                    $cumulative = (int) HourlyProduction::query()
                        ->where('manufacturing_job_id', $job->id)
                        ->where('line_name', $line)
                        ->whereNull('employee_id')
                        ->sum('quantity');

                    $dailyTargetRow = HourlyProductionDailyTarget::query()
                        ->where('manufacturing_job_id', $job->id)
                        ->where('production_date', $selectedDate)
                        ->where('line_name', $line)
                        ->first();

                    $todayProduced = (int) HourlyProduction::query()
                        ->where('manufacturing_job_id', $job->id)
                        ->where('production_date', $selectedDate)
                        ->where('line_name', $line)
                        ->whereNull('employee_id')
                        ->sum('quantity');

                    $dailyTarget = $dailyTargetRow?->target_quantity;
                    $suggestedDaily = max(0, $jobTarget - $cumulative);

                    $lineStats[$line] = [
                        'job_target' => $jobTarget,
                        'daily_target' => $dailyTarget,
                        'today_produced' => $todayProduced,
                        'cumulative_produced' => $cumulative,
                        'remaining_job' => max(0, $jobTarget - $cumulative),
                        'suggested_daily_target' => $suggestedDaily > 0 ? $suggestedDaily : $jobTarget,
                    ];
                }

                if (!$needsTargetSetup) {
                    $records = HourlyProduction::where('manufacturing_job_id', $selectedJobId)
                        ->whereNull('employee_id')
                        ->where('production_date', $selectedDate)
                        ->get();

                    foreach ($records as $rec) {
                        $hourTotalsByLine[$rec->line_name][$rec->hour] = $rec->quantity;
                    }

                    if ($selectedLine) {
                        $hourTotals = $hourTotalsByLine[$selectedLine] ?? [];
                    }
                }

                $totalProduced = HourlyProduction::where('manufacturing_job_id', $selectedJobId)
                    ->whereNull('employee_id')
                    ->sum('quantity');
            }
        }

        return view('erp.production.hourly_create', compact(
            'jobs',
            'selectedJobId',
            'selectedDate',
            'selectedLine',
            'assignedLines',
            'hourTotalsByLine',
            'hourTotals',
            'totalProduced',
            'job',
            'allowedDates',
            'lineStats',
            'needsTargetSetup',
            'jobStartDate',
            'today'
        ));
    }

    public function storeDailyTargets(Request $request)
    {
        $request->validate([
            'manufacturing_job_id' => 'required|exists:manufacturing_jobs,id',
            'production_date' => 'required|date',
            'line_targets' => 'required|array',
            'line_targets.*' => 'required|integer|min:0',
        ]);

        $job = ManufacturingJob::with('lineAssignments')->findOrFail($request->manufacturing_job_id);
        $date = $request->production_date;

        if (!$job->canRecordHourly()) {
            return back()->withErrors(['manufacturing_job_id' => 'Hourly production cannot be recorded for this job in its current stage.']);
        }

        $this->assertDateAllowed($job, $date);

        $allowedLines = $job->lineAssignments->pluck('line_name')->unique()->all();

        foreach ($request->line_targets as $lineName => $targetQty) {
            if (!in_array($lineName, $allowedLines, true)) {
                continue;
            }

            HourlyProductionDailyTarget::updateOrCreate(
                [
                    'manufacturing_job_id' => $job->id,
                    'production_date' => $date,
                    'line_name' => $lineName,
                ],
                [
                    'target_quantity' => (int) $targetQty,
                    'created_by' => auth()->id(),
                ]
            );
        }

        return redirect()
            ->route('hourly-production.create', [
                'job_id' => $job->id,
                'production_date' => $date,
                'line_name' => $request->input('line_name', $allowedLines[0] ?? null),
            ])
            ->with('success', 'Daily targets saved. Now enter hourly output.');
    }

    public function printReport(Request $request)
    {
        $jobId = $request->query('job_id');
        $date  = $request->query('production_date', date('Y-m-d'));

        abort_if(!$jobId, 400, 'Job ID required.');

        $job = ManufacturingJob::with(['lineAssignments'])->findOrFail($jobId);

        $assignedLines = $job->lineAssignments
            ->pluck('line_name')
            ->unique()
            ->values();

        $startHour = 8;
        $endHour   = 20;

        $records = HourlyProduction::where('manufacturing_job_id', $jobId)
            ->whereNull('employee_id')
            ->where('production_date', $date)
            ->get();

        $matrix = [];
        foreach ($records as $rec) {
            $matrix[$rec->hour][$rec->line_name] = $rec->quantity;
        }

        $grandTotal = $records->sum('quantity');

        $dailyTargets = HourlyProductionDailyTarget::query()
            ->where('manufacturing_job_id', $jobId)
            ->where('production_date', $date)
            ->pluck('target_quantity', 'line_name');

        return view('erp.production.hourly_print', compact(
            'job',
            'date',
            'assignedLines',
            'startHour',
            'endHour',
            'matrix',
            'grandTotal',
            'dailyTargets'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'manufacturing_job_id' => 'required|exists:manufacturing_jobs,id',
            'line_name'            => 'required|string',
            'production_date'      => 'required|date',
            'hour_totals'          => 'required|array',
            'hour_totals.*'        => 'nullable|integer|min:0',
        ]);

        $job = ManufacturingJob::with('lineAssignments')->findOrFail($request->manufacturing_job_id);
        $date = $request->production_date;

        if (!$job->canRecordHourly()) {
            return back()->withErrors(['manufacturing_job_id' => 'Hourly production cannot be recorded for this job in its current stage.']);
        }

        $this->assertDateAllowed($job, $date);

        if (!$job->hasDailyTargetsFor($date)) {
            return redirect()
                ->route('hourly-production.create', [
                    'job_id' => $job->id,
                    'production_date' => $date,
                    'line_name' => $request->line_name,
                ])
                ->with('warning', 'මුලින්ම දිනයේ target set කරන්න.');
        }

        $allowedLines = $job->lineAssignments
            ->pluck('line_name')
            ->unique()
            ->values()
            ->all();

        if ($allowedLines === []) {
            return back()->withErrors(['line_name' => 'This job has no assigned lines. Assign lines on the job first.']);
        }

        if (!in_array($request->line_name, $allowedLines, true)) {
            return back()->withErrors(['line_name' => 'Selected line is not assigned to this job.']);
        }

        if ($job->status === ManufacturingJob::STATUS_LINE_ASSIGNED) {
            $job->update(['status' => ManufacturingJob::STATUS_LINE_IN_PROGRESS]);
        }

        $lineName = $request->line_name;
        $saved    = 0;
        $skipped  = 0;

        $lockedHours = HourlyProduction::where('manufacturing_job_id', $job->id)
            ->where('production_date', $date)
            ->where('line_name', $lineName)
            ->whereNull('employee_id')
            ->pluck('hour')
            ->map(fn ($h) => (int) $h)
            ->flip();

        foreach ($request->hour_totals as $hour => $qty) {
            $hour = (int) $hour;

            if ($lockedHours->has($hour)) {
                $skipped++;
                continue;
            }

            if ($qty === null || $qty === '') {
                continue;
            }

            if ((int) $qty <= 0) {
                continue;
            }

            HourlyProduction::create([
                'manufacturing_job_id' => $job->id,
                'production_date'      => $date,
                'hour'                 => $hour,
                'line_name'            => $lineName,
                'employee_id'          => null,
                'quantity'             => (int) $qty,
            ]);
            $saved++;
        }

        if ($saved === 0) {
            $message = $skipped > 0
                ? "No new hours saved. {$skipped} hour(s) already locked."
                : 'Enter at least one hour quantity before saving.';

            return redirect()
                ->route('hourly-production.create', [
                    'job_id' => $job->id,
                    'production_date' => $date,
                    'line_name' => $lineName,
                ])
                ->with('warning', $message);
        }

        $message = "Production saved for {$lineName}. ({$saved} new hour(s) recorded)";
        if ($skipped > 0) {
            $message .= " — {$skipped} locked hour(s) skipped.";
        }

        return redirect()
            ->route('hourly-production.create', [
                'job_id' => $job->id,
                'production_date' => $date,
                'line_name' => $lineName,
            ])
            ->with('success', $message);
    }

    private function assertDateAllowed(ManufacturingJob $job, string $date): void
    {
        $allowed = $job->allowedProductionDates();
        if (!in_array($date, $allowed, true)) {
            abort(422, 'Invalid production date. Only dates from job start until today are allowed.');
        }
    }
}
