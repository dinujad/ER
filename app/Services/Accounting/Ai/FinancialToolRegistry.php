<?php

namespace App\Services\Accounting\Ai;

use App\Services\Accounting\CashFlowForecastService;
use App\Services\Accounting\CostCenterReportService;
use App\Services\Accounting\FinancialStatementService;
use App\Services\Accounting\TaxManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Allowlisted read-only financial tools. Never posts journals.
 */
class FinancialToolRegistry
{
    /** @var list<string> */
    public const ALLOWED = [
        'getProfitAndLoss',
        'getBalanceSheet',
        'getCashFlow',
        'getTrialBalance',
        'getAccountRegister',
        'getRevenueTrend',
        'getExpenseBreakdown',
        'getCustomerAging',
        'getSupplierAging',
        'getBankBalance',
        'getCashFlowForecast',
        'getBudgetVariance',
        'getCostCenterProfitability',
        'getProjectProfitability',
        'getInventoryValuation',
        'getFixedAssetSummary',
        'getDepreciationSummary',
        'getLoanSummary',
        'getTaxSummary',
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function invoke(string $tool, int $businessId, array $filters = [], ?object $user = null): array
    {
        if (! in_array($tool, self::ALLOWED, true)) {
            throw new RuntimeException('Tool not allowlisted: '.$tool);
        }

        $this->assertPermission($tool, $user);

        $from = $filters['from'] ?? date('Y-m-01');
        $to = $filters['to'] ?? date('Y-m-d');
        $asOf = $filters['as_of'] ?? $to;
        $limit = min((int) config('accounting_ai.max_tool_result_rows', 50), max(1, (int) ($filters['limit'] ?? 25)));

        return match ($tool) {
            'getProfitAndLoss' => $this->wrap('getProfitAndLoss', FinancialStatementService::profitAndLoss($from, $to), $from, $to),
            'getBalanceSheet' => $this->wrap('getBalanceSheet', FinancialStatementService::balanceSheet($asOf), null, $asOf),
            'getCashFlow' => $this->wrap('getCashFlow', FinancialStatementService::cashFlowIndirect($from, $to), $from, $to),
            'getTrialBalance' => $this->wrap('getTrialBalance', FinancialStatementService::trialBalance($from, $to), $from, $to),
            'getAccountRegister' => $this->accountRegister($filters, $limit),
            'getRevenueTrend' => $this->revenueTrend($from, $to),
            'getExpenseBreakdown' => $this->expenseBreakdown($from, $to, $limit),
            'getCustomerAging' => $this->agingSummary('ar', $businessId, $limit),
            'getSupplierAging' => $this->agingSummary('ap', $businessId, $limit),
            'getBankBalance' => $this->bankBalance($asOf),
            'getCashFlowForecast' => $this->forecastSummary($filters),
            'getBudgetVariance' => $this->budgetVariance($businessId, $from, $to),
            'getCostCenterProfitability' => $this->costCenterPl($from, $to),
            'getProjectProfitability' => $this->projectProfitability($from, $to, $limit),
            'getInventoryValuation' => $this->inventoryValuation(),
            'getFixedAssetSummary' => $this->fixedAssetSummary($businessId),
            'getDepreciationSummary' => $this->depreciationSummary($businessId, $from, $to),
            'getLoanSummary' => $this->loanSummary($businessId),
            'getTaxSummary' => $this->taxSummary($businessId, $filters),
            default => throw new RuntimeException('Unhandled tool'),
        };
    }

    /**
     * Intent → tool names (deterministic, not LLM).
     *
     * @return list<string>
     */
    public function detectTools(string $question): array
    {
        $q = mb_strtolower($question);
        $tools = [];

        if (preg_match('/profit|net income|ලාභ|ප්‍රොෆිට්|p&l|p \& l/u', $q)) {
            $tools[] = 'getProfitAndLoss';
        }
        if (preg_match('/expense|වියදම|cost increase|වැය/u', $q)) {
            $tools[] = 'getExpenseBreakdown';
            $tools[] = 'getProfitAndLoss';
        }
        if (preg_match('/revenue|sales|ආදායම|compare.*month/u', $q)) {
            $tools[] = 'getRevenueTrend';
            $tools[] = 'getProfitAndLoss';
        }
        if (preg_match('/balance sheet|assets|liabilit|ශේෂ/u', $q)) {
            $tools[] = 'getBalanceSheet';
        }
        if (preg_match('/cash flow|මුදල් ප්‍රවාහ|forecast/u', $q)) {
            $tools[] = 'getCashFlow';
            $tools[] = 'getCashFlowForecast';
        }
        if (preg_match('/cash position|bank balance|මුදල් තත්ත්ව/u', $q)) {
            $tools[] = 'getBankBalance';
        }
        if (preg_match('/overdue|aging|receivable|customer invoice|ගනුදෙනුකරු/u', $q)) {
            $tools[] = 'getCustomerAging';
        }
        if (preg_match('/supplier|payable|outstanding.*supplier|සැපයුම්කරු/u', $q)) {
            $tools[] = 'getSupplierAging';
        }
        if (preg_match('/budget|variance/u', $q)) {
            $tools[] = 'getBudgetVariance';
        }
        if (preg_match('/cost center|department|ලාභදායී/u', $q)) {
            $tools[] = 'getCostCenterProfitability';
        }
        if (preg_match('/project|job cost/u', $q)) {
            $tools[] = 'getProjectProfitability';
        }
        if (preg_match('/inventory|stock valu/u', $q)) {
            $tools[] = 'getInventoryValuation';
        }
        if (preg_match('/depreciation|fixed asset|ක්ෂය/u', $q)) {
            $tools[] = 'getDepreciationSummary';
            $tools[] = 'getFixedAssetSummary';
        }
        if (preg_match('/loan|ණය/u', $q)) {
            $tools[] = 'getLoanSummary';
        }
        if (preg_match('/tax|බදු/u', $q)) {
            $tools[] = 'getTaxSummary';
        }
        if (preg_match('/trial balance|unusual|anomal|summarize.*financial/u', $q)) {
            $tools[] = 'getTrialBalance';
            $tools[] = 'getProfitAndLoss';
        }

        $tools = array_values(array_unique($tools));
        if ($tools === []) {
            $tools = ['getProfitAndLoss', 'getBankBalance'];
        }

        return array_slice($tools, 0, 4);
    }

    protected function assertPermission(string $tool, ?object $user): void
    {
        if ($user === null) {
            throw new RuntimeException('Authentication required for financial tools.');
        }
        $admin = method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin();
        $view = method_exists($user, 'can_access') && $user->can_access('accounting.view');
        if (! $admin && ! $view) {
            throw new RuntimeException('Unauthorized financial tool access.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function wrap(string $tool, array $payload, ?string $from, ?string $to): array
    {
        $primary = $payload['net'] ?? $payload['totals']['asset'] ?? $payload['closing_cash'] ?? null;
        if ($tool === 'getBalanceSheet') {
            $primary = $payload['assets'] ?? $payload['totals']['asset'] ?? null;
        }
        if ($tool === 'getCashFlow') {
            $primary = $payload['net_change'] ?? $payload['closing_cash'] ?? $payload['net'] ?? null;
        }

        return array_merge($payload, [
            'tool' => $tool,
            'source' => 'FinancialStatementService / GL journals',
            'primary_amount' => $primary !== null ? round((float) $primary, 2) : null,
            'period' => trim(($from ?? '').' → '.($to ?? '')),
            'from' => $from,
            'to' => $to,
            'currency' => 'LKR',
            'is_estimate' => false,
        ]);
    }

    /** @return array<string, mixed> */
    protected function accountRegister(array $filters, int $limit): array
    {
        $accountId = (int) ($filters['account_id'] ?? 0);
        if ($accountId <= 0 || ! Schema::hasTable('journal_entries')) {
            return ['tool' => 'getAccountRegister', 'unavailable' => true, 'summary' => 'Account id required or journals missing', 'source' => 'journal_entries'];
        }
        $rows = DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->where('reversed', 0)
            ->where('active', 1)
            ->orderByDesc('date')
            ->limit($limit)
            ->get(['id', 'date', 'transaction_number', 'name', 'debit', 'credit']);

        return [
            'tool' => 'getAccountRegister',
            'source' => 'journal_entries',
            'rows' => $rows,
            'summary' => $rows->count().' journal lines',
            'is_estimate' => false,
            'currency' => 'LKR',
        ];
    }

    /** @return array<string, mixed> */
    protected function revenueTrend(string $from, string $to): array
    {
        $current = FinancialStatementService::profitAndLoss($from, $to);
        $prevFrom = date('Y-m-d', strtotime($from.' -1 month'));
        $prevTo = date('Y-m-d', strtotime($to.' -1 month'));
        $previous = FinancialStatementService::profitAndLoss($prevFrom, $prevTo);
        $curIncome = (float) ($current['income'] ?? 0);
        $prevIncome = (float) ($previous['income'] ?? 0);

        return [
            'tool' => 'getRevenueTrend',
            'source' => 'FinancialStatementService::profitAndLoss income',
            'primary_amount' => round($curIncome, 2),
            'previous_amount' => round($prevIncome, 2),
            'change_amount' => round($curIncome - $prevIncome, 2),
            'from' => $from,
            'to' => $to,
            'period' => "$from → $to",
            'currency' => 'LKR',
            'is_estimate' => false,
        ];
    }

    /** @return array<string, mixed> */
    protected function expenseBreakdown(string $from, string $to, int $limit): array
    {
        $pl = FinancialStatementService::profitAndLoss($from, $to);
        $total = (float) ($pl['expense'] ?? 0);
        $lines = [];
        if (Schema::hasTable('journal_entries') && Schema::hasTable('chart_of_accounts')) {
            $lines = DB::table('journal_entries as j')
                ->join('chart_of_accounts as c', 'c.id', '=', 'j.chart_of_account_id')
                ->where('c.account_type', 'expense')
                ->where('j.reversed', 0)
                ->where('j.active', 1)
                ->whereBetween('j.date', [$from, $to])
                ->groupBy('c.id', 'c.name', 'c.gl_code')
                ->orderByRaw('SUM(COALESCE(j.debit,0)-COALESCE(j.credit,0)) DESC')
                ->limit($limit)
                ->get([
                    'c.gl_code',
                    'c.name',
                    DB::raw('ROUND(SUM(COALESCE(j.debit,0)-COALESCE(j.credit,0)),2) as amount'),
                ]);
        }

        return [
            'tool' => 'getExpenseBreakdown',
            'source' => 'FinancialStatementService + journal_entries expense accounts',
            'primary_amount' => round($total, 2),
            'lines' => $lines,
            'from' => $from,
            'to' => $to,
            'period' => "$from → $to",
            'currency' => 'LKR',
            'is_estimate' => false,
        ];
    }

    /** @return array<string, mixed> */
    protected function agingSummary(string $side, int $businessId, int $limit): array
    {
        if ($side === 'ar' && Schema::hasTable('orders')) {
            $rows = DB::table('orders')
                ->whereIn('status', ['completed', 'partial', 'due', 'pending'])
                ->whereRaw('COALESCE(final_total, total, 0) - COALESCE(amount_paid, paid_amount, 0) > 0.009')
                ->orderBy('transaction_date')
                ->limit($limit)
                ->get(['id', 'customer_id', 'invoice_no', 'transaction_date', 'final_total', 'amount_paid']);
            $total = round((float) $rows->sum(fn ($r) => (float) ($r->final_total ?? 0) - (float) ($r->amount_paid ?? 0)), 2);

            return [
                'tool' => 'getCustomerAging',
                'source' => 'orders outstanding',
                'primary_amount' => $total,
                'rows' => $rows,
                'summary' => $rows->count().' overdue/open invoices',
                'currency' => 'LKR',
                'is_estimate' => false,
            ];
        }

        if ($side === 'ap' && Schema::hasTable('purchases')) {
            $rows = DB::table('purchases')
                ->whereRaw('COALESCE(final_total, total, 0) - COALESCE(amount_paid, paid_amount, 0) > 0.009')
                ->orderBy('purchase_date')
                ->limit($limit)
                ->get(['id', 'supplier_id', 'ref_no', 'purchase_date', 'final_total', 'amount_paid']);
            $total = round((float) $rows->sum(fn ($r) => (float) ($r->final_total ?? 0) - (float) ($r->amount_paid ?? 0)), 2);

            return [
                'tool' => 'getSupplierAging',
                'source' => 'purchases outstanding',
                'primary_amount' => $total,
                'rows' => $rows,
                'summary' => $rows->count().' open supplier bills',
                'currency' => 'LKR',
                'is_estimate' => false,
            ];
        }

        return [
            'tool' => $side === 'ar' ? 'getCustomerAging' : 'getSupplierAging',
            'unavailable' => true,
            'summary' => 'Source tables not available in this environment',
            'source' => 'orders/purchases',
            'is_estimate' => false,
        ];
    }

    /** @return array<string, mixed> */
    protected function bankBalance(string $asOf): array
    {
        $cash = 0.0;
        if (Schema::hasTable('journal_entries') && Schema::hasTable('chart_of_accounts')) {
            $cash = (float) DB::table('journal_entries as j')
                ->join('chart_of_accounts as c', 'c.id', '=', 'j.chart_of_account_id')
                ->where('j.reversed', 0)->where('j.active', 1)
                ->where('j.date', '<=', $asOf)
                ->where(function ($q) {
                    $q->where('c.account_type', 'asset')
                        ->where(function ($q2) {
                            $q2->where('c.name', 'like', '%Cash%')
                                ->orWhere('c.name', 'like', '%Bank%')
                                ->orWhere('c.gl_code', 'like', '11%');
                        });
                })
                ->selectRaw('ROUND(SUM(COALESCE(j.debit,0)-COALESCE(j.credit,0)),2) as bal')
                ->value('bal');
        }

        return [
            'tool' => 'getBankBalance',
            'source' => 'GL cash/bank asset accounts',
            'primary_amount' => round($cash, 2),
            'as_of' => $asOf,
            'period' => $asOf,
            'currency' => 'LKR',
            'is_estimate' => false,
        ];
    }

    /** @return array<string, mixed> */
    protected function forecastSummary(array $filters): array
    {
        $scenarioId = (int) ($filters['scenario_id'] ?? 0);
        if ($scenarioId <= 0 || ! Schema::hasTable('acct_cash_forecast_scenarios')) {
            return [
                'tool' => 'getCashFlowForecast',
                'unavailable' => true,
                'summary' => 'No forecast scenario selected or tables missing',
                'source' => 'CashFlowForecastService',
                'is_estimate' => true,
            ];
        }
        $asOf = $filters['as_of'] ?? date('Y-m-d');
        $result = CashFlowForecastService::runScenario($scenarioId, $asOf);

        return array_merge($result, [
            'tool' => 'getCashFlowForecast',
            'source' => 'CashFlowForecastService (estimate)',
            'primary_amount' => $result['closing_cash'] ?? null,
            'is_estimate' => true,
            'currency' => 'LKR',
        ]);
    }

    /** @return array<string, mixed> */
    protected function budgetVariance(int $businessId, string $from, string $to): array
    {
        if (! Schema::hasTable('acct_budget_versions')) {
            return ['tool' => 'getBudgetVariance', 'unavailable' => true, 'summary' => 'Budget tables missing', 'source' => 'acct_budget_versions', 'is_estimate' => false];
        }

        return [
            'tool' => 'getBudgetVariance',
            'source' => 'acct_budget_versions',
            'summary' => 'Open Budget vs Actual report for detailed variance',
            'drilldown' => url('accounting/budget-versions/vs-actual'),
            'from' => $from,
            'to' => $to,
            'currency' => 'LKR',
            'is_estimate' => false,
        ];
    }

    /** @return array<string, mixed> */
    protected function costCenterPl(string $from, string $to): array
    {
        if (! method_exists(CostCenterReportService::class, 'companyWideProfitAndLoss')) {
            return ['tool' => 'getCostCenterProfitability', 'unavailable' => true, 'summary' => 'Cost center service missing', 'is_estimate' => false];
        }
        $data = CostCenterReportService::companyWideProfitAndLoss($from, $to);

        return array_merge(is_array($data) ? $data : [], [
            'tool' => 'getCostCenterProfitability',
            'source' => 'CostCenterReportService::companyWideProfitAndLoss',
            'primary_amount' => is_array($data) ? ($data['net'] ?? null) : null,
            'from' => $from,
            'to' => $to,
            'currency' => 'LKR',
            'is_estimate' => false,
        ]);
    }

    /** @return array<string, mixed> */
    protected function projectProfitability(string $from, string $to, int $limit): array
    {
        if (! Schema::hasTable('journal_entries') || ! Schema::hasColumn('journal_entries', 'job_id')) {
            return ['tool' => 'getProjectProfitability', 'unavailable' => true, 'summary' => 'job_id not on journals', 'is_estimate' => false];
        }
        $rows = DB::table('journal_entries')
            ->whereNotNull('job_id')
            ->where('reversed', 0)
            ->whereBetween('date', [$from, $to])
            ->groupBy('job_id')
            ->orderBy('job_id')
            ->limit($limit)
            ->get([
                'job_id',
                DB::raw('ROUND(SUM(COALESCE(debit,0)),2) as debits'),
                DB::raw('ROUND(SUM(COALESCE(credit,0)),2) as credits'),
            ]);

        return [
            'tool' => 'getProjectProfitability',
            'source' => 'journal_entries.job_id',
            'rows' => $rows,
            'summary' => $rows->count().' jobs',
            'currency' => 'LKR',
            'is_estimate' => false,
        ];
    }

    /** @return array<string, mixed> */
    protected function inventoryValuation(): array
    {
        $gl = 0.0;
        if (Schema::hasTable('journal_entries') && Schema::hasTable('chart_of_accounts')) {
            $gl = (float) DB::table('journal_entries as j')
                ->join('chart_of_accounts as c', 'c.id', '=', 'j.chart_of_account_id')
                ->where('j.reversed', 0)->where('j.active', 1)
                ->where(function ($q) {
                    $q->where('c.gl_code', 'like', '13%')
                        ->orWhere('c.name', 'like', '%Inventory%')
                        ->orWhere('c.name', 'like', '%Stock%');
                })
                ->selectRaw('ROUND(SUM(COALESCE(j.debit,0)-COALESCE(j.credit,0)),2) as bal')
                ->value('bal');
        }

        return [
            'tool' => 'getInventoryValuation',
            'source' => 'GL inventory-like accounts',
            'primary_amount' => round($gl, 2),
            'currency' => 'LKR',
            'is_estimate' => false,
        ];
    }

    /** @return array<string, mixed> */
    protected function fixedAssetSummary(int $businessId): array
    {
        if (! Schema::hasTable('fa_assets')) {
            return ['tool' => 'getFixedAssetSummary', 'unavailable' => true, 'summary' => 'fa_assets missing', 'is_estimate' => false];
        }
        $q = DB::table('fa_assets');
        if (Schema::hasColumn('fa_assets', 'business_id') && $businessId > 0) {
            $q->where('business_id', $businessId);
        }
        $count = (clone $q)->count();
        $cost = (float) (clone $q)->sum('acquisition_cost');

        return [
            'tool' => 'getFixedAssetSummary',
            'source' => 'fa_assets',
            'primary_amount' => round($cost, 2),
            'asset_count' => $count,
            'currency' => 'LKR',
            'is_estimate' => false,
        ];
    }

    /** @return array<string, mixed> */
    protected function depreciationSummary(int $businessId, string $from, string $to): array
    {
        if (! Schema::hasTable('fa_depreciation_runs')) {
            return ['tool' => 'getDepreciationSummary', 'unavailable' => true, 'summary' => 'depreciation tables missing', 'is_estimate' => false];
        }
        $amount = (float) DB::table('fa_depreciation_runs')
            ->whereBetween('period_end', [$from, $to])
            ->where('status', 'posted')
            ->sum('total_amount');

        return [
            'tool' => 'getDepreciationSummary',
            'source' => 'fa_depreciation_runs',
            'primary_amount' => round($amount, 2),
            'from' => $from,
            'to' => $to,
            'period' => "$from → $to",
            'currency' => 'LKR',
            'is_estimate' => false,
        ];
    }

    /** @return array<string, mixed> */
    protected function loanSummary(int $businessId): array
    {
        if (! Schema::hasTable('acct_loans')) {
            return ['tool' => 'getLoanSummary', 'unavailable' => true, 'summary' => 'acct_loans missing', 'is_estimate' => false];
        }
        $q = DB::table('acct_loans');
        if (Schema::hasColumn('acct_loans', 'business_id') && $businessId > 0) {
            $q->where('business_id', $businessId);
        }
        $outstanding = (float) (clone $q)->sum('principal_outstanding');

        return [
            'tool' => 'getLoanSummary',
            'source' => 'acct_loans',
            'primary_amount' => round($outstanding, 2),
            'currency' => 'LKR',
            'is_estimate' => false,
        ];
    }

    /** @return array<string, mixed> */
    protected function taxSummary(int $businessId, array $filters): array
    {
        if (! Schema::hasTable('acct_tax_periods')) {
            return ['tool' => 'getTaxSummary', 'unavailable' => true, 'summary' => 'tax tables missing', 'is_estimate' => false];
        }
        $periodId = (int) ($filters['tax_period_id'] ?? 0);
        if ($periodId > 0 && class_exists(TaxManagementService::class)) {
            try {
                $summary = TaxManagementService::taxSummary($periodId);

                return array_merge(is_array($summary) ? $summary : [], [
                    'tool' => 'getTaxSummary',
                    'source' => 'TaxManagementService::taxSummary',
                    'currency' => 'LKR',
                    'is_estimate' => false,
                ]);
            } catch (\Throwable) {
                // fall through
            }
        }

        return [
            'tool' => 'getTaxSummary',
            'source' => 'acct_tax_periods',
            'summary' => 'Select a tax period for detailed summary',
            'drilldown' => url('accounting/phase4/tax'),
            'is_estimate' => false,
            'currency' => 'LKR',
        ];
    }
}
