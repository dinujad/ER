<?php

namespace App\Services\Accounting;

use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialKpiAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public static function analyze(string $from, string $to): array
    {
        if (! Schema::hasTable('journal_entries') || ! Schema::hasTable('chart_of_accounts')) {
            return [
                'currency' => 'LKR',
                'from' => $from,
                'to' => $to,
                'gross_margin_pct' => null,
                'net_margin_pct' => null,
                'current_ratio' => null,
                'quick_ratio' => null,
                'dso_days' => null,
                'dpo_days' => null,
                'meta' => ['available' => false],
            ];
        }

        $pl = FinancialStatementService::profitAndLoss($from, $to);
        $revenue = (float) $pl['income'];
        $expense = (float) $pl['expense'];
        $net = (float) $pl['net'];

        $cogsId = AccountingService::accountId(AccountingService::GL_COST_OF_GOODS);
        $cogs = 0.0;
        if ($cogsId) {
            $row = DB::table('journal_entries')
                ->where('chart_of_account_id', $cogsId)
                ->where('reversed', 0)
                ->whereBetween('date', [$from, $to])
                ->selectRaw('COALESCE(SUM(debit),0)-COALESCE(SUM(credit),0) as net')
                ->first();
            $cogs = round((float) ($row->net ?? 0), 2);
        }

        $grossMargin = $revenue != 0.0 ? round((($revenue - $cogs) / $revenue) * 100, 2) : null;
        $netMargin = $revenue != 0.0 ? round(($net / $revenue) * 100, 2) : null;

        $cash = self::assetBalance(AccountingService::GL_CASH) + self::assetBalance(AccountingService::GL_BANK);
        $ar = self::assetBalance(AccountingService::GL_ACCOUNTS_RECV);
        $ap = self::liabilityBalance(AccountingService::GL_ACCOUNTS_PAY);
        $inventory = self::assetBalance(AccountingService::GL_INVENTORY);

        $loanOutstanding = self::loanOutstanding();
        $currentAssets = $cash + $ar + $inventory;
        $currentLiabilities = $ap + $loanOutstanding;

        $currentRatio = $currentLiabilities != 0.0 ? round($currentAssets / $currentLiabilities, 2) : null;
        $quickRatio = $currentLiabilities != 0.0 ? round(($currentAssets - $inventory) / $currentLiabilities, 2) : null;

        $dso = ($revenue != 0.0 && Schema::hasTable('journal_entries'))
            ? round(($ar / $revenue) * (max(1, (int) ((strtotime($to) - strtotime($from)) / 86400) + 1)), 2)
            : null;
        $dpo = ($expense != 0.0)
            ? round(($ap / $expense) * (max(1, (int) ((strtotime($to) - strtotime($from)) / 86400) + 1)), 2)
            : null;

        return [
            'currency' => 'LKR',
            'from' => $from,
            'to' => $to,
            'gross_margin_pct' => $grossMargin,
            'net_margin_pct' => $netMargin,
            'current_ratio' => $currentRatio,
            'quick_ratio' => $quickRatio,
            'dso_days' => $dso,
            'dpo_days' => $dpo,
            'loan_outstanding' => $loanOutstanding,
            'meta' => [
                'div0' => 'Ratios return null when denominator is zero.',
                'current_ratio' => '(cash+bank+AR+inventory) / (AP+loan principal)',
            ],
        ];
    }

    private static function loanOutstanding(): float
    {
        if (Schema::hasTable('acct_loans')) {
            return round((float) DB::table('acct_loans')
                ->whereIn('status', ['active', 'disbursed'])
                ->sum('outstanding_principal'), 2);
        }

        return self::liabilityBalance(AccountingService::GL_LOANS_PAYABLE);
    }

    private static function assetBalance(string $gl): float
    {
        $id = AccountingService::accountId($gl);
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

    private static function liabilityBalance(string $gl): float
    {
        $id = AccountingService::accountId($gl);
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