<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctBudgetVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CostCenterReportService
{
    /**
     * Statutory P&L by cost_center_id on journal lines only.
     * Allocations (acct_cost_allocations) are management-only and excluded.
     *
     * @return array<string, mixed>
     */
    public static function profitAndLoss(?int $costCenterId, string $from, string $to): array
    {
        if (! Schema::hasTable('journal_entries')) {
            return ['income' => '0.00', 'expense' => '0.00', 'net' => '0.00', 'lines' => []];
        }

        $q = DB::table('journal_entries as je')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'je.chart_of_account_id')
            ->where('je.reversed', 0)
            ->whereBetween('je.date', [$from, $to])
            ->whereIn('coa.account_type', ['income', 'expense']);

        if (Schema::hasColumn('journal_entries', 'cost_center_id')) {
            if ($costCenterId === null) {
                $q->whereNull('je.cost_center_id');
            } else {
                $q->where('je.cost_center_id', $costCenterId);
            }
        } elseif ($costCenterId !== null) {
            $q->whereRaw('0 = 1');
        }

        return self::formatProfitAndLossResult($costCenterId, $from, $to, $q);
    }

    /**
     * Statutory company-wide P&L — includes all journal lines regardless of cost_center_id.
     *
     * @return array<string, mixed>
     */
    public static function companyWideProfitAndLoss(string $from, string $to): array
    {
        if (! Schema::hasTable('journal_entries')) {
            return ['income' => '0.00', 'expense' => '0.00', 'net' => '0.00', 'lines' => []];
        }

        $q = DB::table('journal_entries as je')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'je.chart_of_account_id')
            ->where('je.reversed', 0)
            ->whereBetween('je.date', [$from, $to])
            ->whereIn('coa.account_type', ['income', 'expense']);

        $result = self::formatProfitAndLossResult(null, $from, $to, $q);
        unset($result['cost_center_id']);

        return $result;
    }

    /** @param  \Illuminate\Database\Query\Builder  $q */
    private static function formatProfitAndLossResult(
        ?int $costCenterId,
        string $from,
        string $to,
        $q
    ): array {
        $rows = $q->selectRaw('coa.id as account_id, coa.gl_code, coa.name, coa.account_type,
            COALESCE(SUM(je.debit),0) as debits, COALESCE(SUM(je.credit),0) as credits')
            ->groupBy('coa.id', 'coa.gl_code', 'coa.name', 'coa.account_type')
            ->orderBy('coa.gl_code')
            ->get();

        $income = $expense = 0.0;
        $lines = [];
        foreach ($rows as $row) {
            $debits = round((float) $row->debits, 2);
            $credits = round((float) $row->credits, 2);
            if ($row->account_type === 'income') {
                $amount = round($credits - $debits, 2);
                $income += $amount;
            } else {
                $amount = round($debits - $credits, 2);
                $expense += $amount;
            }
            $lines[] = [
                'gl_code' => $row->gl_code,
                'name' => $row->name,
                'account_type' => $row->account_type,
                'amount' => number_format($amount, 2, '.', ''),
            ];
        }

        $net = round($income - $expense, 2);

        return [
            'cost_center_id' => $costCenterId,
            'from' => $from,
            'to' => $to,
            'income' => number_format($income, 2, '.', ''),
            'expense' => number_format($expense, 2, '.', ''),
            'net' => number_format($net, 2, '.', ''),
            'lines' => $lines,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function companyTotals(string $from, string $to): array
    {
        $all = self::profitAndLoss(null, $from, $to);
        unset($all['cost_center_id']);

        $assignedQuery = DB::table('journal_entries as je')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'je.chart_of_account_id')
            ->where('je.reversed', 0)
            ->whereBetween('je.date', [$from, $to])
            ->whereIn('coa.account_type', ['income', 'expense'])
            ->whereNotNull('je.cost_center_id');

        $assignedIncome = (clone $assignedQuery)->where('coa.account_type', 'income')
            ->selectRaw('COALESCE(SUM(credit)-SUM(debit),0) as v')->value('v');
        $assignedExpense = (clone $assignedQuery)->where('coa.account_type', 'expense')
            ->selectRaw('COALESCE(SUM(debit)-SUM(credit),0) as v')->value('v');

        return [
            'unassigned' => $all,
            'assigned_income' => number_format(round((float) $assignedIncome, 2), 2, '.', ''),
            'assigned_expense' => number_format(round((float) $assignedExpense, 2), 2, '.', ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function budgetVsActual(AcctBudgetVersion $version, int $month, ?int $costCenterId = null): array
    {
        return AdvancedBudgetService::budgetVsActual($version, $month, $costCenterId);
    }
}