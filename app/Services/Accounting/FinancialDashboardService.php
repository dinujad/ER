<?php

namespace App\Services\Accounting;

use App\Services\AccountingService;
use App\Services\AccountingWorkspaceMetrics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialDashboardService
{
    public function __construct(
        private AccountingWorkspaceMetrics $workspaceMetrics
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ?: date('Y-01-01');
        $end = $endDate ?: date('Y-m-d');
        $base = $this->workspaceMetrics->summary($start, $end);
        if (! ($base['available'] ?? false)) {
            return $base;
        }

        $cogs = $this->periodExpenseOnGl(AccountingService::GL_COST_OF_GOODS, $start, $end);
        $revenue = (float) ($base['revenue'] ?? 0);
        $opex = round(((float) ($base['expenses'] ?? 0)) - $cogs, 2);
        $grossProfit = round($revenue - $cogs, 2);
        $grossMargin = $revenue != 0.0 ? round(($grossProfit / $revenue) * 100, 2) : null;
        $netProfit = (float) ($base['net_profit'] ?? 0);
        $netMargin = $revenue != 0.0 ? round(($netProfit / $revenue) * 100, 2) : null;

        $inventory = $this->assetNet(AccountingService::GL_INVENTORY)
            + $this->assetNet(AccountingService::GL_RAW_MATERIALS)
            + $this->assetNet(AccountingService::GL_FINISHED_GOODS);
        $faNbv = $this->fixedAssetNbv();
        $loanOutstanding = $this->loanOutstanding();
        $currentAssets = ($base['cash'] ?? 0) + ($base['bank'] ?? 0) + ($base['receivables'] ?? 0) + $inventory;
        $currentLiabilities = ($base['payables'] ?? 0) + $loanOutstanding;
        $currentRatio = $currentLiabilities != 0.0
            ? round($currentAssets / $currentLiabilities, 2)
            : null;
        $workingCapital = round($currentAssets - $currentLiabilities, 2);

        return array_merge($base, [
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_margin_pct' => $grossMargin,
            'opex' => $opex,
            'net_margin_pct' => $netMargin,
            'inventory' => round($inventory, 2),
            'fixed_asset_nbv' => $faNbv,
            'loan_outstanding' => $loanOutstanding,
            'current_ratio' => $currentRatio,
            'working_capital' => $workingCapital,
            'chart' => $this->monthlyRevenueVsExpense($start, $end),
            'meta' => [
                'currency' => 'LKR',
                'formulas' => [
                    'gross_profit' => 'revenue - COGS (GL 5000 period net)',
                    'opex' => 'total_expense - COGS',
                    'current_ratio' => '(cash+bank+AR+inventory) / (AP+loan principal)',
                    'working_capital' => 'current_assets - current_liabilities',
                    'fa_nbv' => 'SUM(fa_assets.net_book_value) when table exists else GL 14xx/15xx - 1450',
                ],
            ],
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function monthlyRevenueVsExpense(string $from, string $to): array
    {
        if (! Schema::hasTable('journal_entries')) {
            return [];
        }

        $salesId = AccountingService::accountId(AccountingService::GL_SALES_REVENUE);
        $returnsId = AccountingService::accountId(AccountingService::GL_SALES_RETURNS);

        $months = [];
        $cursor = strtotime(date('Y-m-01', strtotime($from)));
        $endTs = strtotime(date('Y-m-01', strtotime($to)));
        while ($cursor <= $endTs) {
            $months[] = date('Y-m', $cursor);
            $cursor = strtotime('+1 month', $cursor);
        }

        $series = [];
        foreach ($months as $ym) {
            $mStart = $ym.'-01';
            $mEnd = date('Y-m-t', strtotime($mStart));
            $rev = 0.0;
            if ($salesId || $returnsId) {
                $row = DB::table('journal_entries')
                    ->where('reversed', 0)
                    ->whereBetween('date', [$mStart, $mEnd])
                    ->whereIn('chart_of_account_id', array_filter([$salesId, $returnsId]))
                    ->selectRaw('COALESCE(SUM(credit),0)-COALESCE(SUM(debit),0) as net')
                    ->first();
                $rev = round((float) ($row->net ?? 0), 2);
            }
            $expRow = DB::table('journal_entries as je')
                ->join('chart_of_accounts as coa', 'coa.id', '=', 'je.chart_of_account_id')
                ->where('je.reversed', 0)
                ->whereBetween('je.date', [$mStart, $mEnd])
                ->where('coa.account_type', 'expense')
                ->selectRaw('COALESCE(SUM(je.debit),0)-COALESCE(SUM(je.credit),0) as net')
                ->first();
            $series[] = [
                'month' => $ym,
                'revenue' => $rev,
                'expense' => round((float) ($expRow->net ?? 0), 2),
            ];
        }

        return $series;
    }

    private function periodExpenseOnGl(string $glCode, string $from, string $to): float
    {
        $id = AccountingService::accountId($glCode);
        if (! $id) {
            return 0.0;
        }
        $row = DB::table('journal_entries')
            ->where('chart_of_account_id', $id)
            ->where('reversed', 0)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('COALESCE(SUM(debit),0)-COALESCE(SUM(credit),0) as net')
            ->first();

        return round((float) ($row->net ?? 0), 2);
    }

    private function assetNet(string $glCode): float
    {
        $id = AccountingService::accountId($glCode);
        if (! $id) {
            return 0.0;
        }
        $row = DB::table('journal_entries')
            ->where('chart_of_account_id', $id)
            ->where('reversed', 0)
            ->selectRaw('COALESCE(SUM(debit),0)-COALESCE(SUM(credit),0) as net')
            ->first();

        return round((float) ($row->net ?? 0), 2);
    }

    private function fixedAssetNbv(): ?float
    {
        if (Schema::hasTable('fa_assets') && Schema::hasColumn('fa_assets', 'net_book_value')) {
            return round((float) DB::table('fa_assets')->sum('net_book_value'), 2);
        }

        $asset = $this->assetNet(AccountingService::GL_FIXED_ASSETS) + $this->assetNet(AccountingService::GL_PPE_EQUIPMENT);
        $accum = AccountingService::accountId(AccountingService::GL_ACCUMULATED_DEPRECIATION);
        if ($accum) {
            $row = DB::table('journal_entries')
                ->where('chart_of_account_id', $accum)
                ->where('reversed', 0)
                ->selectRaw('COALESCE(SUM(credit),0)-COALESCE(SUM(debit),0) as net')
                ->first();
            $asset -= round((float) ($row->net ?? 0), 2);
        }

        return round($asset, 2);
    }

    private function loanOutstanding(): float
    {
        if (! Schema::hasTable('acct_loans')) {
            return $this->liabilityNet(AccountingService::GL_LOANS_PAYABLE);
        }

        return round((float) DB::table('acct_loans')
            ->whereIn('status', ['active', 'disbursed'])
            ->sum('outstanding_principal'), 2);
    }

    private function liabilityNet(string $glCode): float
    {
        $id = AccountingService::accountId($glCode);
        if (! $id) {
            return 0.0;
        }
        $row = DB::table('journal_entries')
            ->where('chart_of_account_id', $id)
            ->where('reversed', 0)
            ->selectRaw('COALESCE(SUM(credit),0)-COALESCE(SUM(debit),0) as net')
            ->first();

        return round((float) ($row->net ?? 0), 2);
    }
}