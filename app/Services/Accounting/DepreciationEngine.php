<?php

namespace App\Services\Accounting;

use App\Models\Accounting\FaAsset;
use App\Models\Accounting\FaDepreciationRun;
use App\Models\Accounting\FaDepreciationSchedule;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DepreciationEngine
{
    public static function generateSchedule(FaAsset $asset): void
    {
        if ($asset->depreciation_method === 'none') {
            return;
        }

        if (! $asset->in_service_date) {
            throw new RuntimeException('In-service date is required to generate depreciation schedule.');
        }

        $start = strtotime((string) $asset->in_service_date);
        $cost = $asset->totalCapitalizedCost();
        $salvage = round((float) $asset->salvage_value, 2);
        $life = max(1, (int) $asset->useful_life_months);
        $accum = 0.0;
        $nbv = $cost;

        for ($i = 0; $i < $life; $i++) {
            $ts = strtotime('+ '.$i.' months', $start);
            $year = (int) date('Y', $ts);
            $month = (int) date('n', $ts);
            $periodEnd = date('Y-m-t', $ts);

            $amount = self::periodAmount(
                $asset->depreciation_method,
                $cost,
                $salvage,
                $life,
                $i + 1,
                $nbv,
                $accum
            );

            if ($amount <= 0) {
                break;
            }

            $accum = round($accum + $amount, 2);
            $nbv = round(max($salvage, $cost - $accum), 2);

            FaDepreciationSchedule::updateOrCreate(
                ['asset_id' => $asset->id, 'period_year' => $year, 'period_month' => $month],
                [
                    'period_end' => $periodEnd,
                    'scheduled_amount' => $amount,
                    'accumulated_after' => $accum,
                    'nbv_after' => $nbv,
                    'status' => 'scheduled',
                ]
            );

            if ($nbv <= $salvage) {
                break;
            }
        }
    }

    public static function periodAmount(
        string $method,
        float $cost,
        float $salvage,
        int $lifeMonths,
        int $periodIndex,
        float $currentNbv,
        float $accumulated
    ): float {
        $depreciable = max(0, round($cost - $salvage, 2));
        if ($depreciable <= 0) {
            return 0.0;
        }

        if ($method === 'none') {
            return 0.0;
        }

        if ($method === 'declining_balance') {
            // Double-declining monthly rate from asset useful life (years).
            $years = max(1.0, $lifeMonths / 12.0);
            $monthlyRate = (2.0 / $years) / 12.0;
            $raw = round($currentNbv * $monthlyRate, 2);
            $remainingDepreciable = round($depreciable - $accumulated, 2);
            $remainingToSalvage = round(max(0, $currentNbv - $salvage), 2);

            return round(max(0, min($raw, $remainingDepreciable, $remainingToSalvage)), 2);
        }

        $base = round($depreciable / $lifeMonths, 2);
        $remaining = round($depreciable - $accumulated, 2);
        if ($periodIndex === $lifeMonths || $remaining <= $base) {
            return max(0, round($remaining, 2));
        }

        return min($base, $remaining);
    }

    public static function runPeriod(
        int $businessId,
        int $year,
        int $month,
        string $runDate,
        ?int $userId = null
    ): FaDepreciationRun {
        $runKey = 'depr-run:'.$businessId.':'.$year.'-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT);

        $existing = FaDepreciationRun::where('run_key', $runKey)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($businessId, $year, $month, $runDate, $runKey, $userId) {
            $schedules = FaDepreciationSchedule::query()
                ->whereHas('asset', fn ($q) => $q->where('business_id', $businessId)->where('status', 'active'))
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->where('status', 'scheduled')
                ->with('asset.category')
                ->lockForUpdate()
                ->get();

            $total = 0.0;
            $count = 0;

            foreach ($schedules as $schedule) {
                $asset = $schedule->asset;
                $category = $asset->category;
                $amount = AccountingService::normalizeMoney((float) $schedule->scheduled_amount);

                $expenseId = (int) ($category->depreciation_expense_account_id ?: AccountingService::accountId(AccountingService::GL_DEPRECIATION_EXPENSE));
                $accumId = (int) ($category->accumulated_depreciation_account_id ?: AccountingService::accountId(AccountingService::GL_ACCUMULATED_DEPRECIATION));

                if (! $expenseId || ! $accumId) {
                    throw new RuntimeException('Depreciation GL accounts are not configured.');
                }

                $txn = AccountingService::postOnce(
                    'fa:depr:'.$asset->id.':'.$year.'-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                    'fa_asset',
                    (int) $asset->id,
                    $expenseId,
                    $accumId,
                    $amount,
                    $runDate,
                    'Depreciation '.$asset->name,
                    $asset->asset_code,
                    'depreciation',
                    [
                        'source_type' => 'fa_depreciation',
                        'source_id' => $schedule->id,
                        'cost_center_id' => $asset->cost_center_id,
                    ]
                );

                $schedule->update([
                    'status' => 'posted',
                    'transaction_number' => $txn,
                ]);

                $newAccum = round((float) $asset->accumulated_depreciation + $amount, 2);
                $newNbv = round(max((float) $asset->salvage_value, $asset->totalCapitalizedCost() - $newAccum), 2);
                $newStatus = $newNbv <= (float) $asset->salvage_value ? 'fully_depreciated' : 'active';

                $asset->update([
                    'accumulated_depreciation' => $newAccum,
                    'net_book_value' => $newNbv,
                    'status' => $newStatus,
                ]);

                $total = round($total + $amount, 2);
                $count++;
            }

            $run = FaDepreciationRun::create([
                'business_id' => $businessId,
                'run_key' => $runKey,
                'run_date' => $runDate,
                'period_year' => $year,
                'period_month' => $month,
                'status' => 'posted',
                'total_amount' => $total,
                'asset_count' => $count,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            AccountingAuditService::record('fa_depreciation.run', $run, null, $run->toArray(), null, $businessId, $userId);

            return $run;
        });
    }

    /**
     * Preview scheduled depreciation for a period without posting.
     *
     * @return array{total: float, lines: array<int, array<string, mixed>>}
     */
    public static function previewPeriod(int $businessId, int $year, int $month): array
    {
        $schedules = FaDepreciationSchedule::query()
            ->whereHas('asset', fn ($q) => $q->where('business_id', $businessId)->where('status', 'active'))
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('status', 'scheduled')
            ->with('asset')
            ->get();

        $lines = [];
        $total = 0.0;
        foreach ($schedules as $schedule) {
            $amount = round((float) $schedule->scheduled_amount, 2);
            $total = round($total + $amount, 2);
            $lines[] = [
                'schedule_id' => $schedule->id,
                'asset_id' => $schedule->asset_id,
                'asset_code' => $schedule->asset?->asset_code,
                'asset_name' => $schedule->asset?->name,
                'amount' => number_format($amount, 2, '.', ''),
            ];
        }

        return ['total' => $total, 'lines' => $lines];
    }

    public static function reverseRun(FaDepreciationRun $run, ?int $userId = null): FaDepreciationRun
    {
        if ($run->status !== 'posted') {
            throw new RuntimeException('Only posted depreciation runs can be reversed.');
        }

        return DB::transaction(function () use ($run, $userId) {
            $schedules = FaDepreciationSchedule::query()
                ->where('period_year', $run->period_year)
                ->where('period_month', $run->period_month)
                ->where('status', 'posted')
                ->whereHas('asset', fn ($q) => $q->where('business_id', $run->business_id))
                ->with('asset')
                ->lockForUpdate()
                ->get();

            foreach ($schedules as $schedule) {
                $eventKey = 'fa:depr:'.$schedule->asset_id.':'.$run->period_year.'-'.str_pad((string) $run->period_month, 2, '0', STR_PAD_LEFT);
                AccountingService::reverseEvent($eventKey);

                $amount = round((float) $schedule->scheduled_amount, 2);
                $asset = $schedule->asset;
                $newAccum = round(max(0, (float) $asset->accumulated_depreciation - $amount), 2);
                $newNbv = round($asset->totalCapitalizedCost() - $newAccum, 2);
                $asset->update([
                    'accumulated_depreciation' => $newAccum,
                    'net_book_value' => $newNbv,
                    'status' => 'active',
                ]);
                $schedule->update([
                    'status' => 'scheduled',
                    'transaction_number' => null,
                ]);
            }

            $before = $run->toArray();
            $run->update([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversed_by' => $userId,
            ]);
            // Allow a fresh run for the same period after reverse
            $run->update(['run_key' => $run->run_key.':rev:'.$run->id]);

            AccountingAuditService::record('fa_depreciation.reversed', $run, $before, $run->fresh()->toArray(), null, (int) $run->business_id, $userId);

            return $run->fresh();
        });
    }
}