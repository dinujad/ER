<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctBudgetVersion;
use App\Models\Accounting\AcctBudgetVersionLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AdvancedBudgetService
{
    /**
     * @param  array<string, mixed>  $header
     * @param  array<int, array<string, mixed>>  $lines
     */
    public static function createVersion(array $header, array $lines, ?int $userId = null): AcctBudgetVersion
    {
        $version = AcctBudgetVersion::create([
            'business_id' => (int) ($header['business_id'] ?? 0),
            'budget_id' => $header['budget_id'] ?? null,
            'version_no' => (int) ($header['version_no'] ?? 1),
            'name' => $header['name'],
            'financial_year' => $header['financial_year'],
            'status' => 'draft',
            'cost_center_id' => $header['cost_center_id'] ?? null,
            'department_id' => $header['department_id'] ?? null,
            'job_id' => $header['job_id'] ?? null,
            'notes' => $header['notes'] ?? null,
            'created_by' => $userId,
        ]);

        foreach ($lines as $line) {
            $months = [];
            $annual = 0.0;
            for ($m = 1; $m <= 12; $m++) {
                $val = round((float) ($line['month_'.$m] ?? 0), 2);
                $months['month_'.$m] = $val;
                $annual = round($annual + $val, 2);
            }
            AcctBudgetVersionLine::create(array_merge([
                'budget_version_id' => $version->id,
                'chart_of_account_id' => (int) $line['chart_of_account_id'],
                'annual_total' => $annual,
            ], $months));
        }

        AccountingAuditService::record('budget_version.created', $version, null, $version->load('lines')->toArray(), null, (int) $version->business_id, $userId);

        return $version->fresh('lines');
    }

    public static function submit(AcctBudgetVersion $version, ?int $userId = null): AcctBudgetVersion
    {
        if ($version->status !== 'draft') {
            throw new RuntimeException('Only draft budget versions can be submitted.');
        }
        $before = $version->toArray();
        $version->update(['status' => 'submitted']);
        AccountingAuditService::record('budget_version.submitted', $version, $before, $version->fresh()->toArray(), null, (int) $version->business_id, $userId);

        return $version->fresh('lines');
    }

    public static function approve(AcctBudgetVersion $version, ?int $userId = null): AcctBudgetVersion
    {
        if (! in_array($version->status, ['draft', 'submitted'], true)) {
            throw new RuntimeException('Budget version cannot be approved from status '.$version->status);
        }
        $before = $version->toArray();
        $version->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
        AccountingAuditService::record('budget_version.approved', $version, $before, $version->fresh()->toArray(), null, (int) $version->business_id, $userId);

        return $version->fresh('lines');
    }

    public static function activate(AcctBudgetVersion $version, ?int $userId = null): AcctBudgetVersion
    {
        if ($version->status !== 'approved') {
            throw new RuntimeException('Only approved budget versions can be activated.');
        }

        return DB::transaction(function () use ($version, $userId) {
            AcctBudgetVersion::query()
                ->where('business_id', $version->business_id)
                ->where('financial_year', $version->financial_year)
                ->where('status', 'active')
                ->update(['status' => 'superseded']);

            $before = $version->toArray();
            $version->update(['status' => 'active']);
            AccountingAuditService::record('budget_version.activated', $version, $before, $version->fresh()->toArray(), null, (int) $version->business_id, $userId);

            if ($version->budget_id && Schema::hasTable('budgets')) {
                $updates = ['status' => 'active'];
                if (Schema::hasColumn('budgets', 'active_version_id')) {
                    $updates['active_version_id'] = $version->id;
                }
                if (Schema::hasColumn('budgets', 'version_no')) {
                    $updates['version_no'] = $version->version_no;
                }
                DB::table('budgets')->where('id', $version->budget_id)->update($updates);
            }

            return $version->fresh('lines');
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function budgetVsActual(AcctBudgetVersion $version, int $month, ?int $costCenterId = null): array
    {
        if ($month < 1 || $month > 12) {
            throw new RuntimeException('Month must be between 1 and 12.');
        }

        $year = $version->financial_year;
        $monthKey = 'month_'.$month;
        $rows = [];

        foreach ($version->lines as $line) {
            $budget = $line->monthAmount($month);
            $actual = self::actualForAccount((int) $line->chart_of_account_id, $year, $month, $costCenterId ?? $version->cost_center_id);
            $variance = round($actual - $budget, 2);
            $pct = $budget != 0.0 ? round(($variance / $budget) * 100, 2) : null;

            $rows[] = [
                'chart_of_account_id' => $line->chart_of_account_id,
                'budget' => number_format($budget, 2, '.', ''),
                'actual' => number_format($actual, 2, '.', ''),
                'variance' => number_format($variance, 2, '.', ''),
                'variance_pct' => $pct,
                'favorable' => self::isFavorable($line->chart_of_account_id, $variance),
            ];
        }

        return [
            'financial_year' => $year,
            'month' => $month,
            'version_id' => $version->id,
            'rows' => $rows,
        ];
    }

    private static function actualForAccount(int $accountId, string $year, int $month, ?int $costCenterId): float
    {
        if (! Schema::hasTable('journal_entries')) {
            return 0.0;
        }

        $query = DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->where('year', $year)
            ->where('month', (string) $month)
            ->where('reversed', 0);

        if ($costCenterId && Schema::hasColumn('journal_entries', 'cost_center_id')) {
            $query->where('cost_center_id', $costCenterId);
        }

        $row = $query->selectRaw('COALESCE(SUM(debit),0) as debits, COALESCE(SUM(credit),0) as credits')->first();
        $debits = (float) ($row->debits ?? 0);
        $credits = (float) ($row->credits ?? 0);

        $type = DB::table('chart_of_accounts')->where('id', $accountId)->value('account_type');
        // P&L actuals: expenses as net debit; income as net credit. Skip pure BS semantics here.
        if ($type === 'income') {
            return round($credits - $debits, 2);
        }

        return round($debits - $credits, 2);
    }

    private static function isFavorable(int $accountId, float $variance): bool
    {
        $type = DB::table('chart_of_accounts')->where('id', $accountId)->value('account_type');
        if ($type === 'income') {
            // Higher actual income than budget is favorable
            return $variance >= 0;
        }

        // Higher actual expense than budget is unfavorable
        return $variance <= 0;
    }
}