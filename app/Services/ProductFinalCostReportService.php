<?php

namespace App\Services;

use App\Models\ManufacturingJob;
use Illuminate\Http\Request;

/**
 * Final product cost report — recipe (planned) vs job (actual) with room to extend.
 *
 * Future hooks: variation-level costs, multi-batch rollups, export to accounting snapshots.
 */
class ProductFinalCostReportService
{
    /** @return array<string, mixed> */
    public function buildForJob(ManufacturingJob $job): array
    {
        $job->loadMissing([
            'product.latestRecipe.otherCosts',
            'product.latestRecipe.steps.ingredients.material',
            'packingBatches',
            'additionalCosts',
        ]);

        $recipe = $job->product?->latestRecipe;
        $actual = $job->costBreakdown();
        $outputQty = (float) ($actual['output_qty'] ?? 0);
        $planned = $this->plannedCosts($recipe, $outputQty);
        $actualBlock = $this->actualCosts($actual);
        $variance = $this->variance($planned, $actualBlock);
        $selling = $this->sellingBlock($job, $planned, $actualBlock, $actual);

        $goodCompleted = $job->packingBatches
            ->where('type', 'good')
            ->where('status', 'completed')
            ->sum('quantity');

        return [
            'job' => $job,
            'product' => $job->product,
            'recipe' => $recipe,
            'output_qty' => $outputQty,
            'good_completed_qty' => (float) $goodCompleted,
            'is_converted_to_product' => $goodCompleted > 0,
            'planned' => $planned,
            'actual' => $actualBlock,
            'variance' => $variance,
            'selling' => $selling,
            'recipe_lines' => $this->recipeLineItems($recipe),
            'extensions' => [
                'version' => 1,
                'notes' => 'Extend with variation costs, batch splits, or accounting snapshot IDs here.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function build(string $from, string $to, Request $request): array
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $jobsQuery = ManufacturingJob::query()
            ->with(['product.latestRecipe', 'packingBatches'])
            ->whereNotNull('product_id')
            ->whereHas('packingBatches', function ($q) use ($from, $to) {
                $q->whereIn('status', ['sent_to_final_check', 'completed'])
                    ->whereDate('updated_at', '>=', $from)
                    ->whereDate('updated_at', '<=', $to);
            });

        if ($request->filled('product_id')) {
            $jobsQuery->where('product_id', $request->product_id);
        }

        if ($request->filled('status') && $request->status === 'converted') {
            $jobsQuery->whereHas('packingBatches', fn ($q) => $q->where('type', 'good')->where('status', 'completed'));
        }

        $jobs = $jobsQuery->orderByDesc('updated_at')->limit(200)->get();

        $rows = $jobs->map(fn (ManufacturingJob $job) => $this->buildForJob($job))->values()->all();

        $totals = [
            'job_count' => count($rows),
            'planned_total' => round(collect($rows)->sum(fn ($r) => $r['planned']['scaled_total'] ?? 0), 2),
            'actual_total' => round(collect($rows)->sum(fn ($r) => $r['actual']['total'] ?? 0), 2),
            'variance_total' => round(collect($rows)->sum(fn ($r) => $r['variance']['total'] ?? 0), 2),
        ];

        return [
            'filters' => ['from' => $from, 'to' => $to],
            'rows' => $rows,
            'summary' => $totals,
        ];
    }

    /** @return array<string, mixed> */
    private function plannedCosts(?\App\Models\ProductRecipe $recipe, float $outputQty): array
    {
        if (!$recipe) {
            return [
                'recipe_name' => null,
                'recipe_output_qty' => 0,
                'ingredients_cost' => 0.0,
                'other_costs' => 0.0,
                'total_recipe' => 0.0,
                'per_unit' => 0.0,
                'scaled_total' => 0.0,
                'has_recipe' => false,
            ];
        }

        $recipeOutputQty = max(1.0, (float) ($recipe->total_output_quantity ?? 1));
        $totalRecipe = (float) $recipe->production_cost ?: $recipe->totalRecipeCost();
        $perUnit = round($totalRecipe / $recipeOutputQty, 2);
        $scaledQty = $outputQty > 0 ? $outputQty : $recipeOutputQty;

        return [
            'recipe_name' => $recipe->name,
            'recipe_output_qty' => $recipeOutputQty,
            'ingredients_cost' => round($recipe->ingredientsMaterialCost(), 2),
            'other_costs' => round($recipe->otherCostsTotal(), 2),
            'total_recipe' => round($totalRecipe, 2),
            'per_unit' => $perUnit,
            'scaled_total' => round($perUnit * $scaledQty, 2),
            'has_recipe' => true,
        ];
    }

    /** @param array<string, mixed> $breakdown
     * @return array<string, mixed>
     */
    private function actualCosts(array $breakdown): array
    {
        return [
            'fabric' => (float) ($breakdown['fabric'] ?? 0),
            'accessory' => (float) ($breakdown['accessory'] ?? 0),
            'additional_total' => (float) ($breakdown['additional_total'] ?? 0),
            'overhead_total' => (float) ($breakdown['overhead_total'] ?? 0),
            'total' => (float) ($breakdown['total_with_overhead'] ?? $breakdown['total'] ?? 0),
            'per_unit' => (float) ($breakdown['cost_per_unit_with_overhead'] ?? $breakdown['cost_per_unit'] ?? 0),
            'unit_breakdown' => $breakdown['unit_breakdown'] ?? [],
        ];
    }

    /** @param array<string, mixed> $planned
     * @param array<string, mixed> $actual
     * @return array<string, mixed>
     */
    private function variance(array $planned, array $actual): array
    {
        $total = round($actual['total'] - $planned['scaled_total'], 2);
        $perUnit = round($actual['per_unit'] - $planned['per_unit'], 2);
        $base = (float) $planned['scaled_total'];

        return [
            'total' => $total,
            'per_unit' => $perUnit,
            'percent' => $base > 0 ? round(($total / $base) * 100, 1) : null,
            'status' => $total > 0 ? 'over' : ($total < 0 ? 'under' : 'on_target'),
        ];
    }

    /** @param array<string, mixed> $planned
     * @param array<string, mixed> $actual
     * @param array<string, mixed> $breakdown
     * @return array<string, mixed>
     */
    private function sellingBlock(ManufacturingJob $job, array $planned, array $actual, array $breakdown): array
    {
        $price = isset($breakdown['selling_price']) ? (float) $breakdown['selling_price'] : null;
        if ($price === null && $job->product) {
            $price = (float) $job->product->selling_price;
        }

        return [
            'price' => $price > 0 ? $price : null,
            'profit_per_unit_actual' => $breakdown['profit_per_unit'] ?? ($price > 0 ? round($price - $actual['per_unit'], 2) : null),
            'profit_per_unit_planned' => $price > 0 ? round($price - $planned['per_unit'], 2) : null,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function recipeLineItems(?\App\Models\ProductRecipe $recipe): array
    {
        if (!$recipe) {
            return [];
        }

        $recipe->loadMissing(['otherCosts', 'steps.ingredients.material']);
        $lines = [];

        foreach ($recipe->steps as $step) {
            foreach ($step->ingredients as $ing) {
                $lines[] = [
                    'type' => 'ingredient',
                    'label' => $ing->material?->name ?? 'Material',
                    'amount' => round((float) $ing->cost, 2),
                ];
            }
        }

        foreach ($recipe->otherCosts as $other) {
            $lines[] = [
                'type' => 'other',
                'label' => $other->description ?: 'Other',
                'amount' => round((float) $other->amount, 2),
            ];
        }

        return $lines;
    }
}
