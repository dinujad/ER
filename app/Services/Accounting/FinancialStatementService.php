<?php

namespace App\Services\Accounting;

use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Statutory-style statements from journal_entries + chart_of_accounts (LKR).
 */
class FinancialStatementService
{
    /**
     * @return array<string, mixed>
     */
    public static function profitAndLoss(
        string $from,
        string $to,
        ?string $compareFrom = null,
        ?string $compareTo = null
    ): array {
        $current = self::incomeExpenseTotals($from, $to);
        $compare = ($compareFrom && $compareTo)
            ? self::incomeExpenseTotals($compareFrom, $compareTo)
            : null;

        return [
            'currency' => 'LKR',
            'from' => $from,
            'to' => $to,
            'income' => $current['income'],
            'expense' => $current['expense'],
            'net' => $current['net'],
            'lines' => $current['lines'],
            'compare' => $compare,
            'compare_from' => $compareFrom,
            'compare_to' => $compareTo,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function balanceSheet(string $asOf): array
    {
        $sections = ['asset' => [], 'liability' => [], 'equity' => []];
        $totals = ['asset' => 0.0, 'liability' => 0.0, 'equity' => 0.0];

        foreach (self::accountBalancesThrough($asOf) as $row) {
            $type = $row['account_type'];
            if (! isset($sections[$type])) {
                continue;
            }
            $sections[$type][] = $row;
            $totals[$type] = round($totals[$type] + $row['balance'], 2);
        }

        $periodPl = self::incomeExpenseTotals(
            date('Y-01-01', strtotime($asOf)),
            $asOf
        );
        $currentEarnings = $periodPl['net'];
        $totals['equity'] = round($totals['equity'] + $currentEarnings, 2);

        return [
            'currency' => 'LKR',
            'as_of' => $asOf,
            'sections' => $sections,
            'totals' => $totals,
            'current_earnings' => $currentEarnings,
            'assets' => $totals['asset'],
            'liabilities_plus_equity' => round($totals['liability'] + $totals['equity'], 2),
        ];
    }

    /**
     * Indirect cash flow — classifies non-cash/balance-sheet account movements heuristically.
     * Does NOT reconcile to a single cash GL movement (assumption documented in meta).
     *
     * @return array<string, mixed>
     */
    public static function cashFlowIndirect(string $from, string $to): array
    {
        $buckets = [
            'operating' => 0.0,
            'investing' => 0.0,
            'financing' => 0.0,
            'unclassified' => 0.0,
        ];
        $lines = [];

        $rows = self::periodActivityByAccount($from, $to);
        foreach ($rows as $row) {
            $bucket = self::classifyCashFlowBucket($row['gl_code'], $row['account_type']);
            $amount = self::cashEffect($row);
            $buckets[$bucket] = round($buckets[$bucket] + $amount, 2);
            $lines[] = array_merge($row, ['bucket' => $bucket, 'cash_effect' => $amount]);
        }

        $netChange = round($buckets['operating'] + $buckets['investing'] + $buckets['financing'] + $buckets['unclassified'], 2);

        return [
            'currency' => 'LKR',
            'from' => $from,
            'to' => $to,
            'buckets' => $buckets,
            'net_change' => $netChange,
            'lines' => $lines,
            'meta' => [
                'method' => 'indirect_heuristic',
                'assumptions' => [
                    'Income/expense and working-capital GL codes map to operating.',
                    'FA codes 14xx/15xx map to investing; loan codes 22xx to financing.',
                    'Cash/bank/PDC excluded from classification totals (not invented).',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function changesInEquity(string $from, string $to): array
    {
        $equityAccounts = DB::table('chart_of_accounts')
            ->where('account_type', 'equity')
            ->where('active', 1)
            ->orderBy('gl_code')
            ->get(['id', 'gl_code', 'name']);

        $lines = [];
        $openingTotal = 0.0;
        $closingTotal = 0.0;

        foreach ($equityAccounts as $acc) {
            $openBal = self::balanceThrough((int) $acc->id, date('Y-m-d', strtotime($from.' -1 day')));
            $closeBal = self::balanceThrough((int) $acc->id, $to);
            $movement = round($closeBal - $openBal, 2);
            $lines[] = [
                'gl_code' => $acc->gl_code,
                'name' => $acc->name,
                'opening' => $openBal,
                'movement' => $movement,
                'closing' => $closeBal,
            ];
            $openingTotal = round($openingTotal + $openBal, 2);
            $closingTotal = round($closingTotal + $closeBal, 2);
        }

        $pl = self::incomeExpenseTotals($from, $to);
        $currentEarnings = $pl['net'];

        return [
            'currency' => 'LKR',
            'from' => $from,
            'to' => $to,
            'lines' => $lines,
            'current_period_earnings' => $currentEarnings,
            'opening_equity' => $openingTotal,
            'closing_equity' => round($closingTotal + $currentEarnings, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function trialBalance(string $from, string $to): array
    {
        $rows = [];
        $totalDebit = $totalCredit = 0.0;

        foreach (self::periodActivityByAccount($from, $to) as $row) {
            $debit = $row['debit'] > $row['credit'] ? round($row['debit'] - $row['credit'], 2) : 0.0;
            $credit = $row['credit'] > $row['debit'] ? round($row['credit'] - $row['debit'], 2) : 0.0;
            if ($debit == 0.0 && $credit == 0.0) {
                continue;
            }
            $rows[] = [
                'gl_code' => $row['gl_code'],
                'name' => $row['name'],
                'account_type' => $row['account_type'],
                'debit' => $debit,
                'credit' => $credit,
            ];
            $totalDebit = round($totalDebit + $debit, 2);
            $totalCredit = round($totalCredit + $credit, 2);
        }

        return [
            'currency' => 'LKR',
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ];
    }

    /** @return array{income: float, expense: float, net: float, lines: list<array<string, mixed>>} */
    private static function incomeExpenseTotals(string $from, string $to): array
    {
        $pl = CostCenterReportService::profitAndLoss(null, $from, $to);

        return [
            'income' => (float) $pl['income'],
            'expense' => (float) $pl['expense'],
            'net' => (float) $pl['net'],
            'lines' => $pl['lines'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function accountBalancesThrough(string $asOf): array
    {
        if (! Schema::hasTable('journal_entries')) {
            return [];
        }

        $rows = DB::table('journal_entries as je')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'je.chart_of_account_id')
            ->where('je.reversed', 0)
            ->where('je.date', '<=', $asOf)
            ->where('coa.active', 1)
            ->selectRaw('coa.id, coa.gl_code, coa.name, coa.account_type,
                COALESCE(SUM(je.debit),0) as debits, COALESCE(SUM(je.credit),0) as credits')
            ->groupBy('coa.id', 'coa.gl_code', 'coa.name', 'coa.account_type')
            ->orderBy('coa.gl_code')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $balance = self::signedBalance($row->account_type, (float) $row->debits, (float) $row->credits);
            if (abs($balance) < 0.005) {
                continue;
            }
            $out[] = [
                'account_id' => (int) $row->id,
                'gl_code' => $row->gl_code,
                'name' => $row->name,
                'account_type' => $row->account_type,
                'balance' => $balance,
            ];
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private static function periodActivityByAccount(string $from, string $to): array
    {
        if (! Schema::hasTable('journal_entries')) {
            return [];
        }

        return DB::table('journal_entries as je')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'je.chart_of_account_id')
            ->where('je.reversed', 0)
            ->whereBetween('je.date', [$from, $to])
            ->where('coa.active', 1)
            ->selectRaw('coa.id, coa.gl_code, coa.name, coa.account_type,
                COALESCE(SUM(je.debit),0) as debit, COALESCE(SUM(je.credit),0) as credit')
            ->groupBy('coa.id', 'coa.gl_code', 'coa.name', 'coa.account_type')
            ->orderBy('coa.gl_code')
            ->get()
            ->map(fn ($row) => [
                'account_id' => (int) $row->id,
                'gl_code' => $row->gl_code,
                'name' => $row->name,
                'account_type' => $row->account_type,
                'debit' => round((float) $row->debit, 2),
                'credit' => round((float) $row->credit, 2),
            ])
            ->all();
    }

    private static function balanceThrough(int $accountId, string $asOf): float
    {
        $row = DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->where('reversed', 0)
            ->where('date', '<=', $asOf)
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
            ->first();

        $type = DB::table('chart_of_accounts')->where('id', $accountId)->value('account_type');

        return self::signedBalance((string) $type, (float) ($row->d ?? 0), (float) ($row->c ?? 0));
    }

    private static function signedBalance(string $accountType, float $debits, float $credits): float
    {
        return match ($accountType) {
            'asset', 'expense' => round($debits - $credits, 2),
            default => round($credits - $debits, 2),
        };
    }

    private static function classifyCashFlowBucket(string $glCode, string $accountType): string
    {
        $cashCodes = [
            AccountingService::GL_CASH,
            AccountingService::GL_BANK,
            AccountingService::GL_PDC_RECEIVABLE,
        ];
        if (in_array($glCode, $cashCodes, true)) {
            return 'unclassified';
        }

        if (preg_match('/^14\d{2}$|^15\d{2}$/', $glCode)) {
            return 'investing';
        }
        if (preg_match('/^22\d{2}$/', $glCode) || $accountType === 'equity') {
            return 'financing';
        }
        if (in_array($accountType, ['income', 'expense'], true)) {
            return 'operating';
        }
        if (in_array($accountType, ['asset', 'liability'], true)) {
            return 'operating';
        }

        return 'unclassified';
    }

    /** @param  array<string, mixed>  $row */
    private static function cashEffect(array $row): float
    {
        $type = $row['account_type'];
        if (in_array($type, ['income', 'liability', 'equity'], true)) {
            return round((float) $row['credit'] - (float) $row['debit'], 2);
        }

        return round((float) $row['debit'] - (float) $row['credit'], 2);
    }
}