<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ManufacturingJob;
use App\Models\ManufacturingJobAdditionalCost;
use App\Models\PackingBatch;
use App\Models\QcCheck;
use Carbon\Carbon;

/**
 * Simple Average overhead: monthly Expenses ÷ finished pieces → Rs/piece on each job.
 */
class ManufacturingOverheadService
{
    /**
     * @return array{
     *     total_expenses: float,
     *     finished_pieces: int,
     *     overhead_per_piece: float,
     *     period_from: string,
     *     period_to: string,
     *     pieces_source: string
     * }
     */
    public function simpleAverageForPeriod(string $from, string $to): array
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $totalExpenses = round((float) Expense::query()
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount'), 2);

        $qcFinished = (int) QcCheck::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->sum('finished_good_qty');

        $packingGood = (int) PackingBatch::query()
            ->where('type', 'good')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->sum('quantity');

        $finishedPieces = $qcFinished > 0 ? $qcFinished : $packingGood;
        $piecesSource = $qcFinished > 0 ? 'qc_finished' : ($packingGood > 0 ? 'packing_good' : 'none');

        $overheadPerPiece = $finishedPieces > 0
            ? round($totalExpenses / $finishedPieces, 4)
            : 0.0;

        return [
            'total_expenses' => $totalExpenses,
            'finished_pieces' => $finishedPieces,
            'overhead_per_piece' => $overheadPerPiece,
            'period_from' => $from,
            'period_to' => $to,
            'pieces_source' => $piecesSource,
        ];
    }

    /** Month containing the job's completion (or issue) date. */
    public function allocationMonthForJob(ManufacturingJob $job): array
    {
        $date = $job->updated_at ?? $job->issue_date ?? now();
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        return [
            'from' => $date->copy()->startOfMonth()->toDateString(),
            'to' => $date->copy()->endOfMonth()->toDateString(),
            'label' => $date->format('M Y'),
        ];
    }

    public function overheadPerPieceForJob(ManufacturingJob $job): float
    {
        $month = $this->allocationMonthForJob($job);
        $avg = $this->simpleAverageForPeriod($month['from'], $month['to']);

        return (float) $avg['overhead_per_piece'];
    }

    /**
     * @return array<string, mixed>
     */
    public function enrichCostBreakdown(ManufacturingJob $job, array $breakdown): array
    {
        $month = $this->allocationMonthForJob($job);
        $avg = $this->simpleAverageForPeriod($month['from'], $month['to']);
        $output = max(0, (float) ($breakdown['output_qty'] ?? 0));
        $overheadPerPiece = (float) $avg['overhead_per_piece'];
        $overheadTotal = round($overheadPerPiece * $output, 2);
        $productionTotal = (float) ($breakdown['total'] ?? 0);
        $totalWithOverhead = round($productionTotal + $overheadTotal, 2);

        $sewingTotal = (float) collect($breakdown['additional_by_category'] ?? [])
            ->only([ManufacturingJobAdditionalCost::CAT_SEWING, ManufacturingJobAdditionalCost::CAT_LABOUR])
            ->sum('amount');

        $unitBreakdown = [];
        if ($output > 0) {
            $unitBreakdown = [
                'fabric' => round((float) ($breakdown['fabric'] ?? 0) / $output, 2),
                'accessory' => round((float) ($breakdown['accessory'] ?? 0) / $output, 2),
                'sewing' => round($sewingTotal / $output, 2),
                'overhead' => round($overheadPerPiece, 2),
                'total' => round($totalWithOverhead / $output, 2),
            ];
        }

        $job->loadMissing('product');
        $sellingPrice = null;
        $profitPerUnit = null;
        if ($job->product && (float) $job->product->selling_price > 0) {
            $sellingPrice = (float) $job->product->selling_price;
            if ($output > 0 && isset($unitBreakdown['total'])) {
                $profitPerUnit = round($sellingPrice - $unitBreakdown['total'], 2);
            }
        }

        return array_merge($breakdown, [
            'overhead_per_piece' => round($overheadPerPiece, 2),
            'overhead_total' => $overheadTotal,
            'total_with_overhead' => $totalWithOverhead,
            'cost_per_unit_with_overhead' => $output > 0 ? round($totalWithOverhead / $output, 2) : 0.0,
            'overhead_period_label' => $month['label'],
            'overhead_period_from' => $month['from'],
            'overhead_period_to' => $month['to'],
            'overhead_pieces_in_period' => $avg['finished_pieces'],
            'unit_breakdown' => $unitBreakdown,
            'selling_price' => $sellingPrice,
            'profit_per_unit' => $profitPerUnit,
        ]);
    }
}
