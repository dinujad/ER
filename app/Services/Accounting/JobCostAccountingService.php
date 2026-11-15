<?php

namespace App\Services\Accounting;

use App\Models\ManufacturingJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JobCostAccountingService
{
    /**
     * @return array<string, mixed>
     */
    public static function summarizeJob(ManufacturingJob $job): array
    {
        $breakdown = $job->costBreakdown();

        $totalCost = round((float) ($breakdown['total'] ?? 0), 2);
        $revenue = self::revenueForJob($job);
        $profit = round($revenue - $totalCost, 2);
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : null;

        return [
            'job_id' => $job->id,
            'job_number' => $job->job_number ?? null,
            'status' => $job->status,
            'material_total' => $breakdown['material_total'] ?? 0,
            'additional_total' => $breakdown['additional_total'] ?? 0,
            'overhead' => $breakdown['overhead_total'] ?? ($breakdown['overhead'] ?? 0),
            'total_cost' => $totalCost,
            'output_qty' => $breakdown['output_qty'] ?? 0,
            'cost_per_unit' => $breakdown['cost_per_unit'] ?? 0,
            'revenue' => $revenue,
            'gross_profit' => $profit,
            'profit_margin_pct' => $margin,
            'ledger_debits' => self::ledgerDebitsForJob((int) $job->id),
            'breakdown' => $breakdown,
        ];
    }

    public static function revenueForJob(ManufacturingJob $job): float
    {
        // Prefer explicit order linkage when present; otherwise 0 (do not invent sales).
        if (Schema::hasColumn($job->getTable(), 'order_id') && $job->order_id) {
            if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'total_amount')) {
                return round((float) DB::table('orders')->where('id', $job->order_id)->value('total_amount'), 2);
            }
        }

        return 0.0;
    }

    public static function ledgerDebitsForJob(int $jobId): float
    {
        if (! Schema::hasTable('journal_entries') || ! Schema::hasColumn('journal_entries', 'job_id')) {
            return 0.0;
        }

        return round((float) DB::table('journal_entries')
            ->where('job_id', $jobId)
            ->where('reversed', 0)
            ->sum('debit'), 2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function ledgerLinesForJob(int $jobId): array
    {
        if (! Schema::hasTable('journal_entries') || ! Schema::hasColumn('journal_entries', 'job_id')) {
            return [];
        }

        return DB::table('journal_entries')
            ->where('job_id', $jobId)
            ->where('reversed', 0)
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'account_id' => $row->chart_of_account_id,
                'debit' => round((float) $row->debit, 2),
                'credit' => round((float) $row->credit, 2),
                'transaction_number' => $row->transaction_number,
            ])
            ->all();
    }
}