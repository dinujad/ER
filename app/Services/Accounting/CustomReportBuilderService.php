<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CustomReportBuilderService
{
    public const ALLOWED_TYPES = [
        'profit_and_loss',
        'balance_sheet',
        'trial_balance',
        'cash_flow_indirect',
        'changes_in_equity',
        'cost_center_pl',
        'budget_vs_actual',
    ];

    /**
     * @param  array<string, mixed>  $config
     */
    public static function create(
        int $businessId,
        string $name,
        string $reportType,
        array $config,
        int $userId,
        bool $favorite = false
    ): int {
        self::assertReportType($reportType);
        if (! Schema::hasTable('acct_saved_reports')) {
            throw new RuntimeException('Saved reports table is not available.');
        }

        return (int) DB::table('acct_saved_reports')->insertGetId([
            'business_id' => $businessId,
            'name' => $name,
            'report_type' => $reportType,
            'config' => json_encode($config),
            'is_favorite' => $favorite,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function update(int $id, string $name, array $config, bool $favorite, ?int $businessId = null): void
    {
        if (! Schema::hasTable('acct_saved_reports')) {
            throw new RuntimeException('Saved reports table is not available.');
        }

        $row = DB::table('acct_saved_reports')->where('id', $id)->first();
        if (! $row) {
            throw new RuntimeException('Saved report not found.');
        }
        if ($businessId !== null && (int) $row->business_id !== $businessId) {
            throw new RuntimeException('Saved report not found for this business.');
        }

        DB::table('acct_saved_reports')->where('id', $id)->update([
            'name' => $name,
            'config' => json_encode($config),
            'is_favorite' => $favorite,
            'updated_at' => now(),
        ]);
    }

    public static function delete(int $id, ?int $businessId = null): void
    {
        if (! Schema::hasTable('acct_saved_reports')) {
            return;
        }

        $row = DB::table('acct_saved_reports')->where('id', $id)->first();
        if (! $row) {
            return;
        }
        if ($businessId !== null && (int) $row->business_id !== $businessId) {
            throw new RuntimeException('Saved report not found for this business.');
        }

        DB::table('acct_saved_reports')->where('id', $id)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public static function generate(array $savedReport): array
    {
        $type = $savedReport['report_type'] ?? '';
        self::assertReportType($type);
        $config = is_string($savedReport['config'] ?? null)
            ? json_decode($savedReport['config'], true, 512, JSON_THROW_ON_ERROR)
            : ($savedReport['config'] ?? []);

        return match ($type) {
            'profit_and_loss' => FinancialStatementService::profitAndLoss(
                $config['from'] ?? date('Y-01-01'),
                $config['to'] ?? date('Y-m-d'),
                $config['compare_from'] ?? null,
                $config['compare_to'] ?? null,
            ),
            'balance_sheet' => FinancialStatementService::balanceSheet($config['as_of'] ?? date('Y-m-d')),
            'trial_balance' => FinancialStatementService::trialBalance(
                $config['from'] ?? date('Y-01-01'),
                $config['to'] ?? date('Y-m-d'),
            ),
            'cash_flow_indirect' => FinancialStatementService::cashFlowIndirect(
                $config['from'] ?? date('Y-01-01'),
                $config['to'] ?? date('Y-m-d'),
            ),
            'changes_in_equity' => FinancialStatementService::changesInEquity(
                $config['from'] ?? date('Y-01-01'),
                $config['to'] ?? date('Y-m-d'),
            ),
            'cost_center_pl' => CostCenterReportService::profitAndLoss(
                isset($config['cost_center_id']) ? (int) $config['cost_center_id'] : null,
                $config['from'] ?? date('Y-01-01'),
                $config['to'] ?? date('Y-m-d'),
            ),
            'budget_vs_actual' => self::budgetVsActual($config),
            default => throw new RuntimeException('Unsupported report type.'),
        };
    }

    private static function assertReportType(string $reportType): void
    {
        if (! in_array($reportType, self::ALLOWED_TYPES, true)) {
            throw new RuntimeException("Report type '{$reportType}' is not allowed.");
        }
    }

    /** @param  array<string, mixed>  $config */
    private static function budgetVsActual(array $config): array
    {
        $versionId = (int) ($config['budget_version_id'] ?? 0);
        $month = (int) ($config['month'] ?? (int) date('n'));
        if ($versionId <= 0 || ! Schema::hasTable('acct_budget_versions')) {
            return ['error' => 'budget_version_id required'];
        }
        $version = \App\Models\Accounting\AcctBudgetVersion::query()->findOrFail($versionId);
        $cc = isset($config['cost_center_id']) ? (int) $config['cost_center_id'] : null;

        return AdvancedBudgetService::budgetVsActual($version, $month, $cc);
    }
}