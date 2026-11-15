<?php

namespace App\Services;

use App\Models\ManufacturingJob;
use App\Models\ManufacturingJobAdditionalCost;
use App\Models\ProductRecipeOtherCost;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductionCostReportService
{
    /** @return array<string, mixed> */
    public function build(string $from, string $to, Request $request): array
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $jobsQuery = ManufacturingJob::query()
            ->with(['product', 'additionalCosts.creator']);

        if ($request->get('scope', 'activity') === 'issued') {
            $jobsQuery->whereDate('issue_date', '>=', $from)
                ->whereDate('issue_date', '<=', $to);
        } else {
            $jobsQuery->where(function ($q) use ($from, $to) {
                $q->whereDate('issue_date', '>=', $from)
                    ->whereDate('issue_date', '<=', $to)
                    ->orWhereBetween('updated_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
            });
        }

        if ($request->filled('job_id')) {
            $jobsQuery->where('id', $request->job_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $jobsQuery->where('status', $request->status);
        }

        $jobs = $jobsQuery->orderBy('job_number')->limit(500)->get();

        $categoryTotals = [];
        foreach (ManufacturingJobAdditionalCost::categoryOptions() as $key => $label) {
            $categoryTotals[$key] = ['label' => $label, 'amount' => 0.0];
        }

        $jobRows = [];
        $additionalLines = collect();
        $totals = [
            'fabric' => 0.0,
            'accessory' => 0.0,
            'material' => 0.0,
            'additional' => 0.0,
            'production' => 0.0,
            'output_qty' => 0,
        ];

        foreach ($jobs as $job) {
            $costs = $job->costBreakdown();

            $totals['fabric'] += $costs['fabric'];
            $totals['accessory'] += $costs['accessory'];
            $totals['material'] += $costs['material_total'];
            $totals['additional'] += $costs['additional_total'];
            $totals['production'] += $costs['total'];
            $totals['overhead'] = ($totals['overhead'] ?? 0) + ($costs['overhead_total'] ?? 0);
            $totals['output_qty'] += (int) $costs['output_qty'];

            foreach ($costs['additional_by_category'] as $key => $cat) {
                if (isset($categoryTotals[$key])) {
                    $categoryTotals[$key]['amount'] += $cat['amount'];
                }
            }

            $jobRows[] = [
                'job' => $job,
                'fabric' => $costs['fabric'],
                'accessory' => $costs['accessory'],
                'additional_by_category' => $costs['additional_by_category'],
                'additional_total' => $costs['additional_total'],
                'overhead_total' => $costs['overhead_total'] ?? 0,
                'total' => $costs['total_with_overhead'] ?? $costs['total'],
                'production_total' => $costs['total'],
                'output_qty' => $costs['output_qty'],
                'cost_per_unit' => $costs['cost_per_unit_with_overhead'] ?? $costs['cost_per_unit'],
                'profit_per_unit' => $costs['profit_per_unit'] ?? null,
            ];

            foreach ($job->additionalCosts as $line) {
                $additionalLines->push([
                    'job_number' => $job->job_number,
                    'job_id' => $job->id,
                    'category' => $line->category,
                    'category_label' => $line->categoryLabel(),
                    'description' => $line->description,
                    'amount' => (float) $line->amount,
                    'created_at' => $line->created_at,
                ]);
            }
        }

        foreach ($categoryTotals as $key => $row) {
            $categoryTotals[$key]['amount'] = round($row['amount'], 2);
        }

        $recipeOtherLines = ProductRecipeOtherCost::query()
            ->with(['recipe.product'])
            ->orderBy('product_recipe_id')
            ->orderBy('order_index')
            ->get();

        $recipeOtherByDescription = $recipeOtherLines
            ->groupBy(fn ($row) => trim($row->description ?: 'Other'))
            ->map(fn (Collection $group) => round((float) $group->sum('amount'), 2))
            ->sortDesc();

        return [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'scope' => $request->get('scope', 'activity'),
                'status' => $request->get('status', 'all'),
                'job_id' => $request->get('job_id'),
            ],
            'jobs' => $jobs,
            'job_count' => $jobs->count(),
            'job_rows' => $jobRows,
            'category_totals' => $categoryTotals,
            'additional_lines' => $additionalLines->sortByDesc('amount')->values(),
            'recipe_other_lines' => $recipeOtherLines,
            'recipe_other_by_description' => $recipeOtherByDescription,
            'recipe_other_total' => round((float) $recipeOtherLines->sum('amount'), 2),
            'totals' => array_map(fn ($v) => is_numeric($v) ? round((float) $v, 2) : $v, $totals),
            'filter_jobs' => ManufacturingJob::orderBy('job_number')->get(['id', 'job_number', 'style_ref']),
            'status_options' => ManufacturingReportService::jobStatusOptions(),
        ];
    }
}
