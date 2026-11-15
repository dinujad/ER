<?php

namespace App\Services;

use App\Models\HourlyProduction;
use App\Models\HourlyProductionDailyTarget;
use App\Models\ManufacturingJob;
use Carbon\Carbon;

class DashboardProductionSummary
{
    /** @return array<string, mixed> */
    public function build(string $fromStr, string $toStr): array
    {
        $today = now()->toDateString();
        $todayInRange = $today >= $fromStr && $today <= $toStr;

        $activeStatuses = [
            ManufacturingJob::STATUS_LINE_ASSIGNED,
            ManufacturingJob::STATUS_LINE_IN_PROGRESS,
            ManufacturingJob::STATUS_WASHING_OUT,
            ManufacturingJob::STATUS_AFTER_WASH_RECEIVED,
        ];

        $jobs = ManufacturingJob::query()
            ->with(['product', 'lineAssignments'])
            ->whereIn('status', $activeStatuses)
            ->whereHas('lineAssignments')
            ->orderBy('job_number')
            ->limit(50)
            ->get();

        $jobIds = $jobs->pluck('id');

        $hourlyInPeriod = HourlyProduction::query()
            ->lineTotals()
            ->whereIn('manufacturing_job_id', $jobIds)
            ->whereDate('production_date', '>=', $fromStr)
            ->whereDate('production_date', '<=', $toStr)
            ->get()
            ->groupBy(fn ($r) => $r->manufacturing_job_id . '|' . $r->line_name);

        $hourlyToday = $todayInRange
            ? HourlyProduction::query()
                ->lineTotals()
                ->whereIn('manufacturing_job_id', $jobIds)
                ->whereDate('production_date', $today)
                ->get()
                ->groupBy(fn ($r) => $r->manufacturing_job_id . '|' . $r->line_name)
            : collect();

        $hourlyAllTime = HourlyProduction::query()
            ->lineTotals()
            ->whereIn('manufacturing_job_id', $jobIds)
            ->selectRaw('manufacturing_job_id, line_name, SUM(quantity) as total')
            ->groupBy('manufacturing_job_id', 'line_name')
            ->get()
            ->keyBy(fn ($r) => $r->manufacturing_job_id . '|' . $r->line_name);

        $targetsInPeriod = HourlyProductionDailyTarget::query()
            ->whereIn('manufacturing_job_id', $jobIds)
            ->whereDate('production_date', '>=', $fromStr)
            ->whereDate('production_date', '<=', $toStr)
            ->get()
            ->groupBy(fn ($r) => $r->manufacturing_job_id . '|' . $r->line_name);

        $targetsToday = $todayInRange
            ? HourlyProductionDailyTarget::query()
                ->whereIn('manufacturing_job_id', $jobIds)
                ->whereDate('production_date', $today)
                ->get()
                ->keyBy(fn ($r) => $r->manufacturing_job_id . '|' . $r->line_name)
            : collect();

        $jobRows = [];
        $totals = [
            'jobs' => 0,
            'lines' => 0,
            'job_target' => 0,
            'total_produced' => 0,
            'period_produced' => 0,
            'period_target' => 0,
            'today_produced' => 0,
            'today_target' => 0,
        ];

        foreach ($jobs as $job) {
            if ($job->lineAssignments->isEmpty()) {
                continue;
            }

            $lines = [];
            foreach ($job->lineAssignments as $assignment) {
                $line = $assignment->line_name;
                $key = $job->id . '|' . $line;
                $jobTarget = (int) ($assignment->assigned_quantity ?? 0);
                $totalProduced = (int) ($hourlyAllTime->get($key)?->total ?? 0);
                $periodProduced = (int) ($hourlyInPeriod->get($key)?->sum('quantity') ?? 0);
                $todayProduced = (int) ($hourlyToday->get($key)?->sum('quantity') ?? 0);
                $periodTarget = (int) ($targetsInPeriod->get($key)?->sum('target_quantity') ?? 0);
                $todayTarget = (int) ($targetsToday->get($key)?->target_quantity ?? 0);

                $lines[] = [
                    'line_name' => $line,
                    'job_target' => $jobTarget,
                    'total_produced' => $totalProduced,
                    'period_produced' => $periodProduced,
                    'period_target' => $periodTarget,
                    'today_produced' => $todayProduced,
                    'today_target' => $todayTarget,
                    'remaining_job' => max(0, $jobTarget - $totalProduced),
                    'period_balance' => max(0, $periodTarget - $periodProduced),
                    'today_balance' => max(0, $todayTarget - $todayProduced),
                ];

                $totals['lines']++;
                $totals['job_target'] += $jobTarget;
                $totals['total_produced'] += $totalProduced;
                $totals['period_produced'] += $periodProduced;
                $totals['period_target'] += $periodTarget;
                $totals['today_produced'] += $todayProduced;
                $totals['today_target'] += $todayTarget;
            }

            if ($lines === []) {
                continue;
            }

            $totals['jobs']++;
            $jobRows[] = [
                'job' => $job,
                'lines' => $lines,
                'job_total_target' => array_sum(array_column($lines, 'job_target')),
                'job_total_produced' => array_sum(array_column($lines, 'total_produced')),
                'job_period_produced' => array_sum(array_column($lines, 'period_produced')),
            ];
        }

        $totals['period_achievement_pct'] = $totals['period_target'] > 0
            ? round(($totals['period_produced'] / $totals['period_target']) * 100, 1)
            : null;

        $totals['today_achievement_pct'] = $totals['today_target'] > 0
            ? round(($totals['today_produced'] / $totals['today_target']) * 100, 1)
            : null;

        return [
            'job_rows' => $jobRows,
            'totals' => $totals,
            'today_in_range' => $todayInRange,
            'today_label' => Carbon::parse($today)->format('d M Y'),
        ];
    }
}
