<?php

namespace App\Services;

use App\Models\ManufacturingJob;
use App\Models\QcCheck;
use App\Models\WashingTransfer;
use Illuminate\Http\Request;

class ManufacturingReportService
{
    public function __construct(
        protected DashboardProductionSummary $productionSummary,
        protected WashingReportQuery $washingReportQuery
    ) {}

    /** @return array<string, mixed> */
    public function build(string $from, string $to, Request $request): array
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $filters = [
            'from' => $from,
            'to' => $to,
            'status' => $request->get('status', 'all'),
            'job_id' => $request->get('job_id'),
            'search' => trim((string) $request->get('search', '')),
            'scope' => $request->get('scope', 'activity'),
        ];

        $jobsQuery = ManufacturingJob::query()
            ->with(['product', 'lineAssignments', 'materialIssues.material', 'packingBatches.materialIssues.material', 'materialIssue.material', 'additionalCosts']);

        if ($filters['scope'] === 'issued') {
            $jobsQuery->whereDate('issue_date', '>=', $from)
                ->whereDate('issue_date', '<=', $to);
        } else {
            $jobsQuery->where(function ($q) use ($from, $to) {
                $q->whereDate('issue_date', '>=', $from)
                    ->whereDate('issue_date', '<=', $to)
                    ->orWhereBetween('updated_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                    ->orWhereHas('hourlyProductions', fn ($h) => $h->whereDate('production_date', '>=', $from)->whereDate('production_date', '<=', $to))
                    ->orWhereHas('washingTransfers', fn ($w) => $w->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to));
            });
        }

        if ($filters['status'] !== 'all' && $filters['status'] !== '') {
            $jobsQuery->where('status', $filters['status']);
        }

        if (!empty($filters['job_id'])) {
            $jobsQuery->where('id', $filters['job_id']);
        }

        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $jobsQuery->where(function ($q) use ($s) {
                $q->where('job_number', 'like', "%{$s}%")
                    ->orWhere('style_ref', 'like', "%{$s}%")
                    ->orWhere('batch_ref', 'like', "%{$s}%");
            });
        }

        $jobs = $jobsQuery->orderBy('job_number')->limit(500)->get();

        $jobDetails = $jobs->map(function (ManufacturingJob $job) {
            $costs = $job->costBreakdown();

            return [
                'job' => $job,
                'line_target' => $job->progressTargetPieces(),
                'line_produced' => $job->totalLineHourlyQuantity(),
                'washing_sent' => $job->totalWashingSentQuantity(),
                'washing_available' => $job->availableForWashingQuantity(),
                'cut_pieces' => (int) ($job->total_cut_pieces ?? 0),
                'fabric_used' => (float) ($job->fabric_used_qty ?? 0),
                'fabric_cost' => $costs['fabric'],
                'accessory_cost' => $costs['accessory'],
                'additional_cost' => $costs['additional_total'],
                'overhead_cost' => $costs['overhead_total'] ?? 0,
                'total_cost' => $costs['total_with_overhead'] ?? $costs['total'],
                'production_cost' => $costs['total'],
                'cost_per_unit' => $costs['cost_per_unit_with_overhead'] ?? $costs['cost_per_unit'],
                'profit_per_unit' => $costs['profit_per_unit'] ?? null,
            ];
        });

        $production = $this->productionSummary->build($from, $to);

        $washingRequest = Request::create('/', 'GET', [
            'date_from' => $from,
            'date_to' => $to,
            'status' => $request->get('wash_status', 'all'),
            'record_type' => $request->get('wash_record_type', 'all'),
            'job_id' => $filters['job_id'],
            'washing_type' => $request->get('washing_type', 'all'),
            'washing_factory' => $request->get('washing_factory', ''),
            'vehicle_number' => $request->get('vehicle_number', ''),
            'sent_from' => $request->get('sent_from', ''),
            'search' => $filters['search'],
        ]);

        $washFilters = $this->washingReportQuery->filtersFromRequest($washingRequest);
        $washingRows = $this->washingReportQuery->baseQuery($washFilters)->limit(2000)->get();
        $washingSummary = $this->washingReportQuery->summary($washingRows);
        $washingOptions = $this->washingReportQuery->filterOptions();

        $qcChecks = QcCheck::query()
            ->with(['washingTransfer.job.product'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->when($filters['job_id'], function ($q, $jobId) {
                $q->whereHas('washingTransfer', fn ($w) => $w->where('manufacturing_job_id', $jobId));
            })
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $statusCounts = ManufacturingJob::query()
            ->whereIn('id', $jobs->pluck('id'))
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        return [
            'filters' => $filters,
            'jobs' => $jobs,
            'job_details' => $jobDetails,
            'job_count' => $jobs->count(),
            'status_counts' => $statusCounts,
            'production' => $production,
            'washing_rows' => $washingRows,
            'washing_summary' => $washingSummary,
            'washing_filters' => $washFilters,
            'washing_options' => $washingOptions,
            'qc_checks' => $qcChecks,
            'qc_total_checked' => (int) $qcChecks->sum('total_checked'),
            'qc_finished' => (int) $qcChecks->sum('finished_good_qty'),
            'qc_damaged' => (int) $qcChecks->sum('damaged_qty'),
            'filter_jobs' => ManufacturingJob::orderBy('job_number')->get(['id', 'job_number', 'style_ref']),
            'status_options' => $this->jobStatusOptions(),
        ];
    }

    public static function jobStatusOptions(): array
    {
        return [
            ManufacturingJob::STATUS_FABRIC_ISSUED => 'Fabric Issued',
            ManufacturingJob::STATUS_SENT_TO_CUTTING => 'Sent to Cutting',
            ManufacturingJob::STATUS_CUTTING_IN_PROGRESS => 'Cutting In Progress',
            ManufacturingJob::STATUS_CUTTING_COMPLETED => 'Cutting Completed',
            ManufacturingJob::STATUS_LINE_ASSIGNED => 'Line Assigned',
            ManufacturingJob::STATUS_LINE_IN_PROGRESS => 'Line In Progress',
            ManufacturingJob::STATUS_LINE_COMPLETED => 'Line Completed',
            ManufacturingJob::STATUS_WASHING_OUT => 'Washing Out',
            ManufacturingJob::STATUS_AFTER_WASH_RECEIVED => 'After Wash Received',
            ManufacturingJob::STATUS_PACKING_COMPLETED => 'Packing Completed',
            ManufacturingJob::STATUS_WAREHOUSE_RECEIVED => 'Warehouse Received',
            ManufacturingJob::STATUS_JOB_ENDED => 'Job Ended',
        ];
    }
}
