<?php

namespace App\Services\Accounting;

use App\Models\Accounting\MfgOverheadRule;
use App\Models\Accounting\MfgOverheadRun;
use App\Models\Accounting\MfgOverheadRunLine;
use App\Models\ManufacturingJob;
use App\Models\ManufacturingJobAdditionalCost;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ManufacturingOverheadAllocationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function createRule(array $data, ?int $userId = null): MfgOverheadRule
    {
        $rule = MfgOverheadRule::create([
            'name' => $data['name'],
            'business_id' => (int) ($data['business_id'] ?? 0),
            'allocation_base' => $data['allocation_base'],
            'rate' => round((float) ($data['rate'] ?? 0), 4),
            'percentage' => isset($data['percentage']) ? round((float) $data['percentage'], 4) : null,
            'overhead_pool_account_id' => (int) $data['overhead_pool_account_id'],
            'applied_clearing_account_id' => $data['applied_clearing_account_id'] ?? null,
            'wip_account_id' => $data['wip_account_id'] ?? AccountingService::accountId(AccountingService::GL_WORK_IN_PROGRESS),
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
        ]);

        AccountingAuditService::record('mfg_oh_rule.created', $rule, null, $rule->toArray(), null, (int) $rule->business_id, $userId);

        return $rule;
    }

    public static function approveRule(MfgOverheadRule $rule, ?int $userId = null): MfgOverheadRule
    {
        if ($rule->status !== 'draft') {
            throw new RuntimeException('Only draft rules can be approved.');
        }
        $rule->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);

        return $rule->fresh();
    }

    public static function activateRule(MfgOverheadRule $rule, ?int $userId = null): MfgOverheadRule
    {
        if ($rule->status !== 'approved') {
            throw new RuntimeException('Only approved rules can be activated.');
        }
        $rule->update(['status' => 'active']);
        AccountingAuditService::record('mfg_oh_rule.activated', $rule, null, $rule->fresh()->toArray(), null, (int) $rule->business_id, $userId);

        return $rule->fresh();
    }

    /**
     * @return array{run: MfgOverheadRun, lines: array<int, array<string, mixed>>}
     */
    public static function preview(MfgOverheadRule $rule, string $periodFrom, string $periodTo): array
    {
        if (! Schema::hasTable('manufacturing_jobs')) {
            throw new RuntimeException('Manufacturing jobs table not available.');
        }

        $runKey = self::runKey($rule->id, $periodFrom, $periodTo);
        $existing = MfgOverheadRun::query()->where('run_key', $runKey)->first();
        if ($existing && $existing->status === 'posted') {
            throw new RuntimeException('A posted run already exists for this period (run_key '.$runKey.').');
        }

        $jobs = ManufacturingJob::query()
            ->whereBetween('issue_date', [$periodFrom, $periodTo])
            ->get();

        $lines = [];
        $totalBase = $totalAllocated = 0.0;

        foreach ($jobs as $job) {
            $base = self::allocationBaseQuantity($rule, $job);
            if ($base <= 0) {
                continue;
            }
            $allocated = self::allocateAmount($rule, $base);
            $totalBase = round($totalBase + $base, 2);
            $totalAllocated = round($totalAllocated + $allocated, 2);
            $lines[] = [
                'job_id' => $job->id,
                'job_number' => $job->job_number,
                'base_quantity' => number_format($base, 2, '.', ''),
                'allocated_amount' => number_format($allocated, 2, '.', ''),
            ];
        }

        $run = MfgOverheadRun::query()->updateOrCreate(
            ['run_key' => $runKey],
            [
                'rule_id' => $rule->id,
                'business_id' => (int) $rule->business_id,
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'status' => 'preview',
                'total_base' => $totalBase,
                'total_allocated' => $totalAllocated,
            ]
        );

        MfgOverheadRunLine::query()->where('run_id', $run->id)->delete();
        foreach ($lines as $line) {
            MfgOverheadRunLine::create([
                'run_id' => $run->id,
                'job_id' => (int) $line['job_id'],
                'base_quantity' => $line['base_quantity'],
                'allocated_amount' => $line['allocated_amount'],
                'event_key' => 'mfg:oh:'.$run->id.':'.$line['job_id'],
            ]);
        }

        return ['run' => $run->fresh('lines'), 'lines' => $lines];
    }

    public static function postRun(MfgOverheadRun $run, ?int $userId = null): MfgOverheadRun
    {
        if ($run->status === 'posted') {
            return $run;
        }

        $rule = $run->rule;
        $wipId = (int) ($rule->wip_account_id ?: AccountingService::accountId(AccountingService::GL_WORK_IN_PROGRESS));
        $creditId = (int) ($rule->applied_clearing_account_id ?: $rule->overhead_pool_account_id);
        if (! $wipId || ! $creditId) {
            throw new RuntimeException('WIP or overhead pool GL account missing.');
        }

        return DB::transaction(function () use ($run, $rule, $wipId, $creditId, $userId) {
            $locked = MfgOverheadRun::query()->lockForUpdate()->find($run->id);
            if ($locked->status === 'posted') {
                return $locked;
            }

            $postingDate = $locked->period_to->format('Y-m-d');
            foreach ($locked->lines as $line) {
                $amount = AccountingService::normalizeMoney((float) $line->allocated_amount);
                if ($amount <= 0) {
                    continue;
                }
                $txn = AccountingService::postOnce(
                    $line->event_key ?? ('mfg:oh:'.$locked->id.':'.$line->job_id),
                    'mfg_overhead_run_line',
                    (int) $line->id,
                    $wipId,
                    $creditId,
                    $amount,
                    $postingDate,
                    'Manufacturing overhead — '.$rule->name,
                    $rule->name,
                    'mfg_overhead_allocation',
                    ['source_type' => 'mfg_overhead_run', 'source_id' => $locked->id, 'job_id' => $line->job_id]
                );
                $line->update(['transaction_number' => $txn]);
            }

            $locked->update([
                'status' => 'posted',
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            AccountingAuditService::record('mfg_oh_run.posted', $locked, null, $locked->fresh()->toArray(), null, (int) $locked->business_id, $userId);

            return $locked->fresh('lines');
        });
    }

    public static function reverseRun(MfgOverheadRun $run, ?int $userId = null): MfgOverheadRun
    {
        if ($run->status !== 'posted') {
            throw new RuntimeException('Only posted runs can be reversed.');
        }

        foreach ($run->lines as $line) {
            if ($line->event_key) {
                AccountingService::reverseEvent($line->event_key);
            }
        }

        $run->update(['status' => 'reversed', 'reversed_at' => now()]);
        AccountingAuditService::record('mfg_oh_run.reversed', $run, null, $run->fresh()->toArray(), null, (int) $run->business_id, $userId);

        return $run->fresh();
    }

    private static function runKey(int $ruleId, string $from, string $to): string
    {
        return 'mfg-oh:'.$ruleId.':'.$from.':'.$to;
    }

    private static function allocationBaseQuantity(MfgOverheadRule $rule, ManufacturingJob $job): float
    {
        $job->loadMissing('additionalCosts');

        return match ($rule->allocation_base) {
            'labor_hours' => self::laborHoursBase($job),
            'machine_hours' => 0.0,
            'production_qty' => (float) $job->totalOutputQty(),
            'labor_cost' => (float) $job->additionalCosts
                ->whereIn('category', [ManufacturingJobAdditionalCost::CAT_LABOUR, 'labor'])
                ->sum('amount'),
            default => 0.0,
        };
    }

    /**
     * No labor_hours column on manufacturing_jobs — derive from additional cost category labor or 0.
     */
    private static function laborHoursBase(ManufacturingJob $job): float
    {
        $labor = (float) $job->additionalCosts
            ->whereIn('category', [ManufacturingJobAdditionalCost::CAT_LABOUR, 'labor'])
            ->sum('amount');

        return $labor > 0 ? round($labor, 2) : 0.0;
    }

    private static function allocateAmount(MfgOverheadRule $rule, float $base): float
    {
        if ($rule->percentage !== null && (float) $rule->percentage > 0) {
            return AccountingService::normalizeMoney($base * ((float) $rule->percentage / 100));
        }

        return AccountingService::normalizeMoney($base * (float) $rule->rate);
    }
}