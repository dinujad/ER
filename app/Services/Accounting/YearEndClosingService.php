<?php

namespace App\Services\Accounting;

use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class YearEndClosingService
{
    /**
     * @return array<string, mixed>
     */
    public static function buildChecklist(int $financialYearId): array
    {
        $fy = DB::table('acct_financial_years')->where('id', $financialYearId)->first();
        if (! $fy) {
            throw new RuntimeException('Financial year not found.');
        }

        $tb = FinancialStatementService::trialBalance($fy->starts_on, $fy->ends_on);
        $tbBalanced = number_format($tb['total_debit'], 2, '.', '') === number_format($tb['total_credit'], 2, '.', '');

        $unpostedManual = 0;
        // Phase 2 table name is accounting_manual_journals (not acct_manual_journals).
        $manualTable = Schema::hasTable('accounting_manual_journals')
            ? 'accounting_manual_journals'
            : (Schema::hasTable('acct_manual_journals') ? 'acct_manual_journals' : null);
        if ($manualTable) {
            $unpostedManual = (int) DB::table($manualTable)
                ->whereIn('status', ['draft', 'submitted', 'approved'])
                ->count();
        }

        $openPeriods = Schema::hasTable('accounting_periods')
            ? (int) DB::table('accounting_periods')
                ->where('business_id', $fy->business_id)
                ->where('status', 'open')
                ->whereBetween('starts_on', [$fy->starts_on, $fy->ends_on])
                ->count()
            : 0;

        $arId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_RECV);
        $apId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_PAY);
        $arBal = $arId ? self::balance($arId) : 0.0;
        $apBal = $apId ? self::balance($apId) : 0.0;

        return [
            'financial_year' => $fy,
            'trial_balance_balanced' => $tbBalanced,
            'unposted_manual_journals' => $unpostedManual,
            'open_accounting_periods' => $openPeriods,
            'accounts_receivable_balance' => $arBal,
            'accounts_payable_balance' => $apBal,
            'ready' => $tbBalanced && $unpostedManual === 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function previewClosing(int $financialYearId, int $retainedEarningsAccountId): array
    {
        $fy = DB::table('acct_financial_years')->where('id', $financialYearId)->first();
        if (! $fy) {
            throw new RuntimeException('Financial year not found.');
        }
        $pl = FinancialStatementService::profitAndLoss($fy->starts_on, $fy->ends_on);
        $netIncome = (float) $pl['net'];

        $incomeLines = self::periodAccountsByType($fy->starts_on, $fy->ends_on, 'income');
        $expenseLines = self::periodAccountsByType($fy->starts_on, $fy->ends_on, 'expense');

        return [
            'net_income' => $netIncome,
            'retained_earnings_account_id' => $retainedEarningsAccountId,
            'income_accounts' => $incomeLines,
            'expense_accounts' => $expenseLines,
            'method' => count($incomeLines) + count($expenseLines) > 0 ? 'multi_line_close' : 'single_net_entry',
        ];
    }

    public static function executeClosing(
        int $financialYearId,
        int $retainedEarningsAccountId,
        ?int $userId = null
    ): string {
        return DB::transaction(function () use ($financialYearId, $retainedEarningsAccountId, $userId) {
            $fy = DB::table('acct_financial_years')->where('id', $financialYearId)->lockForUpdate()->first();
            if (! $fy) {
                throw new RuntimeException('Financial year not found.');
            }

            $checklist = self::buildChecklist($financialYearId);
            if (! $checklist['ready']) {
                throw new RuntimeException(
                    'Year-end checklist not ready: '
                    .json_encode([
                        'trial_balance_balanced' => $checklist['trial_balance_balanced'],
                        'unposted_manual_journals' => $checklist['unposted_manual_journals'],
                    ])
                );
            }

            $session = DB::table('acct_year_end_sessions')
                ->where('financial_year_id', $financialYearId)
                ->orderByDesc('id')
                ->first();

            $defaultKey = 'year-end:'.$financialYearId;
            $eventKey = $defaultKey;
            if ($session && $session->event_key) {
                $sessionKey = (string) $session->event_key;
                if ($session->status === 'reopened' || ! self::hasLivePosting($sessionKey)) {
                    $eventKey = $sessionKey;
                }
            }

            if ($fy->status === 'closed') {
                $postingKey = $session?->event_key ? (string) $session->event_key : $defaultKey;
                if ($session?->closing_txn && self::hasLivePosting($postingKey)) {
                    return (string) $session->closing_txn;
                }

                throw new RuntimeException('Financial year already closed without live closing posting.');
            }

            if (self::hasLivePosting($eventKey) && $session?->closing_txn) {
                return (string) $session->closing_txn;
            }

            $preview = self::previewClosing($financialYearId, $retainedEarningsAccountId);
            $lines = [];
            $netIncome = 0.0;

            foreach ($preview['income_accounts'] as $row) {
                $bal = (float) $row['balance'];
                if (abs($bal) < 0.005) {
                    continue;
                }
                if ($bal > 0) {
                    $lines[] = ['account_id' => (int) $row['account_id'], 'debit' => $bal, 'credit' => 0];
                    $netIncome = round($netIncome + $bal, 2);
                } else {
                    $lines[] = ['account_id' => (int) $row['account_id'], 'debit' => 0, 'credit' => abs($bal)];
                    $netIncome = round($netIncome + $bal, 2);
                }
            }

            foreach ($preview['expense_accounts'] as $row) {
                $bal = (float) $row['balance'];
                if (abs($bal) < 0.005) {
                    continue;
                }
                if ($bal > 0) {
                    $lines[] = ['account_id' => (int) $row['account_id'], 'debit' => 0, 'credit' => $bal];
                    $netIncome = round($netIncome - $bal, 2);
                } else {
                    $lines[] = ['account_id' => (int) $row['account_id'], 'debit' => abs($bal), 'credit' => 0];
                    $netIncome = round($netIncome - $bal, 2);
                }
            }

            if ($lines === []) {
                $pl = FinancialStatementService::profitAndLoss($fy->starts_on, $fy->ends_on);
                $netIncome = (float) $pl['net'];
            }

            if (abs($netIncome) >= 0.01) {
                if ($netIncome > 0) {
                    $lines[] = ['account_id' => $retainedEarningsAccountId, 'debit' => 0, 'credit' => $netIncome];
                } else {
                    $lines[] = ['account_id' => $retainedEarningsAccountId, 'debit' => abs($netIncome), 'credit' => 0];
                }
            }

            if ($lines === []) {
                throw new RuntimeException('Nothing to close for this period.');
            }

            $txn = AccountingService::postMultiLine(
                $lines,
                $fy->ends_on,
                'Year-end closing '.$fy->name,
                $fy->name,
                'year_end_close',
                ['source_type' => 'financial_year', 'source_id' => $financialYearId],
                $eventKey,
                (int) $fy->business_id
            );

            $sessionPayload = [
                'status' => 'executed',
                'checklist' => json_encode($checklist),
                'closing_txn' => $txn,
                'retained_earnings_account_id' => $retainedEarningsAccountId,
                'preview' => json_encode($preview),
                'executed_by' => $userId,
                'executed_at' => now(),
                'event_key' => $eventKey,
                'updated_at' => now(),
            ];

            if ($session) {
                DB::table('acct_year_end_sessions')->where('id', $session->id)->update($sessionPayload);
                $sessionId = (int) $session->id;
            } else {
                $sessionId = (int) DB::table('acct_year_end_sessions')->insertGetId(array_merge($sessionPayload, [
                    'financial_year_id' => $financialYearId,
                    'created_at' => now(),
                ]));
            }

            DB::table('acct_financial_years')->where('id', $financialYearId)->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $userId,
                'updated_at' => now(),
            ]);

            AccountingAuditService::record(
                'year_end.executed',
                (object) ['id' => $sessionId],
                null,
                ['closing_txn' => $txn],
                'Year-end closing executed',
                (int) $fy->business_id,
                $userId
            );

            return $txn;
        });
    }

    public static function reopen(int $financialYearId, ?int $userId = null, ?string $reason = null): void
    {
        $fy = DB::table('acct_financial_years')->where('id', $financialYearId)->first();
        if (! $fy || $fy->status !== 'closed') {
            throw new RuntimeException('Financial year is not closed.');
        }

        $session = DB::table('acct_year_end_sessions')
            ->where('financial_year_id', $financialYearId)
            ->orderByDesc('id')
            ->first();

        $keysToReverse = ['year-end:'.$financialYearId];
        if ($session && $session->event_key) {
            $keysToReverse[] = (string) $session->event_key;
        }
        foreach (array_unique($keysToReverse) as $key) {
            AccountingService::reverseEvent($key);
        }

        $newKey = 'year-end:'.$financialYearId.':v'.time();

        if ($session) {
            DB::table('acct_year_end_sessions')->where('id', $session->id)->update([
                'status' => 'reopened',
                'closing_txn' => null,
                'event_key' => $newKey,
                'updated_at' => now(),
            ]);
        }

        DB::table('acct_financial_years')->where('id', $financialYearId)->update([
            'status' => 'open',
            'closed_at' => null,
            'closed_by' => null,
            'updated_at' => now(),
        ]);

        AccountingAuditService::record(
            'year_end.reopened',
            (object) ['id' => $financialYearId],
            ['status' => 'closed'],
            ['status' => 'open', 'event_key' => $newKey],
            $reason ?? 'Year-end reopened',
            (int) $fy->business_id,
            $userId
        );
    }

    private static function hasLivePosting(string $eventKey): bool
    {
        if (! Schema::hasTable('accounting_postings')) {
            return false;
        }

        return DB::table('accounting_postings')
            ->where('event_key', $eventKey)
            ->where('reversed', 0)
            ->exists();
    }

    /** @return list<array<string, mixed>> */
    private static function periodAccountsByType(string $from, string $to, string $type): array
    {
        $rows = DB::table('journal_entries as je')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'je.chart_of_account_id')
            ->where('je.reversed', 0)
            ->whereBetween('je.date', [$from, $to])
            ->where('coa.account_type', $type)
            ->selectRaw('coa.id as account_id, coa.gl_code, coa.name,
                COALESCE(SUM(je.debit),0) as debits, COALESCE(SUM(je.credit),0) as credits')
            ->groupBy('coa.id', 'coa.gl_code', 'coa.name')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $balance = $type === 'income'
                ? round((float) $row->credits - (float) $row->debits, 2)
                : round((float) $row->debits - (float) $row->credits, 2);
            if (abs($balance) < 0.005) {
                continue;
            }
            $out[] = [
                'account_id' => (int) $row->account_id,
                'gl_code' => $row->gl_code,
                'name' => $row->name,
                'balance' => $balance,
            ];
        }

        return $out;
    }

    private static function balance(int $accountId): float
    {
        $row = DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->where('reversed', 0)
            ->selectRaw('COALESCE(SUM(debit),0)-COALESCE(SUM(credit),0) as net')
            ->first();

        return round((float) ($row->net ?? 0), 2);
    }
}