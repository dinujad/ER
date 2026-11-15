<?php

namespace App\Services\Accounting;

use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rule-based cash forecast — read-only; never posts journals.
 */
class CashFlowForecastService
{
    /**
     * @param  array<string, mixed>  $assumptions
     * @return array<string, mixed>
     */
    public static function runScenario(
        int $scenarioId,
        string $asOfDate,
        ?int $userId = null
    ): array {
        if (! Schema::hasTable('acct_cash_forecast_scenarios')) {
            return ['error' => 'Forecast tables missing'];
        }

        $scenario = DB::table('acct_cash_forecast_scenarios')->where('id', $scenarioId)->first();
        if (! $scenario) {
            return ['error' => 'Scenario not found'];
        }

        $assumptions = json_decode($scenario->assumptions ?? '{}', true) ?: [];
        $collectionPct = max(0, min(100, (float) ($assumptions['collection_pct'] ?? 100))) / 100;
        $delayDays = max(0, (int) ($assumptions['delay_days'] ?? 0));
        $horizon = (int) $scenario->horizon_days;

        $opening = self::openingCash();
        $inflows = self::estimateInflows($asOfDate, $horizon, $collectionPct, $delayDays);
        $outflows = self::estimateOutflows($asOfDate, $horizon);
        $totalIn = round(array_sum(array_column($inflows, 'amount')), 2);
        $totalOut = round(array_sum(array_column($outflows, 'amount')), 2);
        $closing = round($opening + $totalIn - $totalOut, 2);

        $detail = [
            'inflows' => $inflows,
            'outflows' => $outflows,
            'assumptions_applied' => compact('collectionPct', 'delayDays'),
        ];

        if (Schema::hasTable('acct_cash_forecast_runs')) {
            DB::table('acct_cash_forecast_runs')->insert([
                'scenario_id' => $scenarioId,
                'as_of_date' => $asOfDate,
                'opening_cash' => $opening,
                'total_inflows' => $totalIn,
                'total_outflows' => $totalOut,
                'closing_cash' => $closing,
                'detail' => json_encode($detail),
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'currency' => 'LKR',
            'as_of_date' => $asOfDate,
            'horizon_days' => $horizon,
            'opening_cash' => $opening,
            'total_inflows' => $totalIn,
            'total_outflows' => $totalOut,
            'closing_cash' => $closing,
            'detail' => $detail,
        ];
    }

    private static function openingCash(): float
    {
        $cash = AccountingService::accountId(AccountingService::GL_CASH);
        $bank = AccountingService::accountId(AccountingService::GL_BANK);
        $ids = array_filter([$cash, $bank]);
        if ($ids === []) {
            return 0.0;
        }
        $row = DB::table('journal_entries')
            ->whereIn('chart_of_account_id', $ids)
            ->where('reversed', 0)
            ->selectRaw('COALESCE(SUM(debit),0)-COALESCE(SUM(credit),0) as net')
            ->first();

        return round((float) ($row->net ?? 0), 2);
    }

    /** @return list<array<string, mixed>> */
    private static function estimateInflows(
        string $asOf,
        int $horizonDays,
        float $collectionPct,
        int $delayDays
    ): array {
        $items = [];
        $to = date('Y-m-d', strtotime($asOf." +{$horizonDays} days"));

        if (Schema::hasTable('transactions')) {
            $rows = DB::table('transactions')
                ->when(Schema::hasColumn('transactions', 'payment_status'), fn ($q) => $q->where('payment_status', '!=', 'paid'))
                ->when(Schema::hasColumn('transactions', 'type'), fn ($q) => $q->where('type', 'sell'))
                ->when(Schema::hasColumn('transactions', 'transaction_date'), fn ($q) => $q->whereBetween('transaction_date', [$asOf, $to]))
                ->limit(50)
                ->get();

            foreach ($rows as $row) {
                $amount = self::transactionRemainingAmount($row);
                $due = isset($row->transaction_date)
                    ? date('Y-m-d', strtotime((string) $row->transaction_date." +{$delayDays} days"))
                    : $asOf;
                $items[] = [
                    'source' => 'ar_order',
                    'reference' => $row->invoice_no ?? $row->id,
                    'due_date' => $due,
                    'amount' => round($amount * $collectionPct, 2),
                ];
            }
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    private static function estimateOutflows(string $asOf, int $horizonDays): array
    {
        $items = [];
        $to = date('Y-m-d', strtotime($asOf." +{$horizonDays} days"));

        if (Schema::hasTable('transactions')) {
            $rows = DB::table('transactions')
                ->when(Schema::hasColumn('transactions', 'payment_status'), fn ($q) => $q->where('payment_status', '!=', 'paid'))
                ->when(Schema::hasColumn('transactions', 'type'), fn ($q) => $q->where('type', 'purchase'))
                ->when(Schema::hasColumn('transactions', 'transaction_date'), fn ($q) => $q->whereBetween('transaction_date', [$asOf, $to]))
                ->limit(50)
                ->get();

            foreach ($rows as $row) {
                $items[] = [
                    'source' => 'ap_purchase',
                    'reference' => $row->ref_no ?? $row->id,
                    'due_date' => $row->transaction_date ?? $asOf,
                    'amount' => self::transactionRemainingAmount($row),
                ];
            }
        }

        if (Schema::hasTable('acct_loan_schedule_lines')) {
            $loanRows = DB::table('acct_loan_schedule_lines')
                ->whereBetween('due_date', [$asOf, $to])
                ->where('status', '!=', 'paid')
                ->limit(50)
                ->get();
            foreach ($loanRows as $row) {
                $items[] = [
                    'source' => 'loan_payment',
                    'reference' => 'schedule:'.$row->id,
                    'due_date' => $row->due_date,
                    'amount' => round((float) ($row->total_payment ?? 0), 2),
                ];
            }
        }

        return $items;
    }

    private static function transactionRemainingAmount(object $row): float
    {
        $total = (float) ($row->final_total ?? $row->total ?? 0);
        if (Schema::hasColumn('transactions', 'amount_paid')) {
            return round(max(0, $total - (float) $row->amount_paid), 2);
        }
        if (Schema::hasColumn('transactions', 'payment_status') && ($row->payment_status ?? '') === 'paid') {
            return 0.0;
        }
        if (Schema::hasTable('transaction_payments') && isset($row->id)) {
            $paid = (float) DB::table('transaction_payments')->where('transaction_id', $row->id)->sum('amount');

            return round(max(0, $total - $paid), 2);
        }

        return round($total, 2);
    }
}