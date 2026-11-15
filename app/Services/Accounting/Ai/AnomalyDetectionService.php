<?php

namespace App\Services\Accounting\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deterministic financial anomaly rules — alerts for review only.
 * Never reverses or deletes transactions.
 */
class AnomalyDetectionService
{
    /**
     * Seed default rules for a business (idempotent by unique name/type).
     */
    public function ensureDefaultRules(int $businessId): void
    {
        if (! Schema::hasTable('acct_anomaly_rules')) {
            return;
        }

        $defaults = [
            ['name' => 'Duplicate invoice fingerprint', 'rule_type' => 'duplicate_invoice', 'threshold' => null, 'severity' => 'high'],
            ['name' => 'Duplicate payment attempt', 'rule_type' => 'duplicate_payment', 'threshold' => null, 'severity' => 'high'],
            ['name' => 'Large unexpected transaction', 'rule_type' => 'large_transaction', 'threshold' => 500000, 'severity' => 'medium'],
            ['name' => 'Unusual expense increase', 'rule_type' => 'expense_increase', 'threshold' => 50, 'severity' => 'medium'],
            ['name' => 'Reconciliation difference', 'rule_type' => 'recon_difference', 'threshold' => 0.01, 'severity' => 'high'],
            ['name' => 'Repeated reversals', 'rule_type' => 'repeated_reversals', 'threshold' => 3, 'severity' => 'medium'],
            ['name' => 'AR aging deterioration', 'rule_type' => 'ar_aging', 'threshold' => 30, 'severity' => 'medium'],
            ['name' => 'AP aging deterioration', 'rule_type' => 'ap_aging', 'threshold' => 30, 'severity' => 'medium'],
            ['name' => 'Budget overrun', 'rule_type' => 'budget_overrun', 'threshold' => 10, 'severity' => 'medium'],
            ['name' => 'Cash shortage', 'rule_type' => 'cash_shortage', 'threshold' => 0, 'severity' => 'high'],
            ['name' => 'Tax control mismatch', 'rule_type' => 'tax_mismatch', 'threshold' => 0.01, 'severity' => 'high'],
            ['name' => 'Abnormal discount', 'rule_type' => 'abnormal_discount', 'threshold' => 40, 'severity' => 'medium'],
            ['name' => 'Unusual refund frequency', 'rule_type' => 'refund_frequency', 'threshold' => 5, 'severity' => 'medium'],
            ['name' => 'Loan repayment delay', 'rule_type' => 'loan_delay', 'threshold' => 1, 'severity' => 'medium'],
            ['name' => 'Negative unexpected balance', 'rule_type' => 'negative_balance', 'threshold' => 0, 'severity' => 'medium'],
            ['name' => 'Unusual journal pattern', 'rule_type' => 'journal_pattern', 'threshold' => 10, 'severity' => 'low'],
        ];

        foreach ($defaults as $rule) {
            $exists = DB::table('acct_anomaly_rules')
                ->where('business_id', $businessId)
                ->where('rule_type', $rule['rule_type'])
                ->where('name', $rule['name'])
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('acct_anomaly_rules')->insert([
                'business_id' => $businessId,
                'name' => $rule['name'],
                'rule_type' => $rule['rule_type'],
                'threshold' => $rule['threshold'],
                'severity' => $rule['severity'],
                'enabled' => true,
                'effective_from' => now()->toDateString(),
                'config' => json_encode([]),
                'notify' => 'dashboard',
                'rule_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function run(int $businessId): array
    {
        $this->ensureDefaultRules($businessId);
        $alerts = [];

        $rules = Schema::hasTable('acct_anomaly_rules')
            ? DB::table('acct_anomaly_rules')->where('business_id', $businessId)->where('enabled', true)->get()
            : collect();

        foreach ($rules as $rule) {
            $found = match ($rule->rule_type) {
                'duplicate_invoice' => $this->detectDuplicateInvoices($businessId, $rule),
                'duplicate_payment' => $this->detectDuplicatePayments($businessId, $rule),
                'large_transaction' => $this->detectLargeTransactions($businessId, $rule),
                'expense_increase' => $this->detectExpenseIncrease($businessId, $rule),
                'repeated_reversals' => $this->detectRepeatedReversals($businessId, $rule),
                'cash_shortage' => $this->detectCashShortage($businessId, $rule),
                default => [],
            };
            foreach ($found as $alert) {
                $alerts[] = $this->persistAlert($businessId, $rule, $alert);
            }
        }

        return array_values(array_filter($alerts));
    }

    public function setStatus(int $businessId, int $alertId, string $status, ?int $userId = null, ?string $note = null): void
    {
        if (! in_array($status, ['new', 'under_review', 'resolved', 'dismissed'], true)) {
            throw new \RuntimeException('Invalid alert status.');
        }
        DB::table('acct_anomaly_alerts')
            ->where('id', $alertId)
            ->where('business_id', $businessId)
            ->update([
                'status' => $status,
                'assigned_to' => $userId,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  object  $rule
     * @param  array{alert_type:string, reference_type:?string, reference_id:?int, amount:?float, explanation:string, evidence:array}  $alert
     * @return array<string, mixed>|null
     */
    protected function persistAlert(int $businessId, object $rule, array $alert): ?array
    {
        if (! Schema::hasTable('acct_anomaly_alerts')) {
            return $alert;
        }

        $key = sha1($businessId.'|'.$rule->rule_type.'|'.$alert['reference_type'].'|'.$alert['reference_id'].'|'.($alert['amount'] ?? ''));
        $existing = DB::table('acct_anomaly_alerts')->where('idempotency_key', $key)->first();
        if ($existing) {
            return (array) $existing;
        }

        $id = (int) DB::table('acct_anomaly_alerts')->insertGetId([
            'business_id' => $businessId,
            'rule_id' => $rule->id,
            'alert_type' => $alert['alert_type'],
            'severity' => $rule->severity,
            'reference_type' => $alert['reference_type'],
            'reference_id' => $alert['reference_id'],
            'amount' => $alert['amount'],
            'currency' => 'LKR',
            'explanation' => $alert['explanation'],
            'evidence' => json_encode($alert['evidence']),
            'rule_version' => (int) $rule->rule_version,
            'status' => 'new',
            'idempotency_key' => $key,
            'detected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('acct_ai_review_tasks')) {
            DB::table('acct_ai_review_tasks')->updateOrInsert(
                [
                    'business_id' => $businessId,
                    'subject_type' => 'anomaly_alert',
                    'subject_id' => $id,
                    'task_type' => 'anomaly',
                ],
                [
                    'title' => 'Anomaly: '.$alert['alert_type'],
                    'suggestion_payload' => json_encode($alert),
                    'confidence' => null,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return array_merge($alert, ['id' => $id, 'idempotency_key' => $key, 'status' => 'new']);
    }

    /** @return list<array<string, mixed>> */
    protected function detectDuplicateInvoices(int $businessId, object $rule): array
    {
        $out = [];
        if (! Schema::hasTable('acct_ocr_extractions')) {
            return $out;
        }
        $rows = DB::table('acct_ocr_extractions')->where('business_id', $businessId)->where('duplicate_suspected', true)->limit(50)->get();
        foreach ($rows as $row) {
            $out[] = [
                'alert_type' => 'duplicate_invoice',
                'reference_type' => 'ocr_document',
                'reference_id' => (int) $row->ocr_document_id,
                'amount' => null,
                'explanation' => 'Possible duplicate supplier invoice detected. Review before posting. Not an accusation of fraud.',
                'evidence' => ['extraction_id' => $row->id, 'reason' => $row->duplicate_reason, 'rule' => $rule->name],
            ];
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    protected function detectDuplicatePayments(int $businessId, object $rule): array
    {
        $out = [];
        if (! Schema::hasTable('payments')) {
            return $out;
        }
        $dupes = DB::table('payments')
            ->select('reference_number', 'amount', DB::raw('COUNT(*) as c'), DB::raw('MIN(id) as first_id'))
            ->whereNotNull('reference_number')
            ->where('reference_number', '!=', '')
            ->groupBy('reference_number', 'amount')
            ->having('c', '>', 1)
            ->limit(20)
            ->get();
        foreach ($dupes as $d) {
            $out[] = [
                'alert_type' => 'duplicate_payment',
                'reference_type' => 'payment',
                'reference_id' => (int) $d->first_id,
                'amount' => round((float) $d->amount, 2),
                'explanation' => 'Repeated payment reference/amount pattern ('.$d->c.' rows). Review for duplicate payment attempts.',
                'evidence' => ['reference' => $d->reference_number, 'count' => $d->c],
            ];
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    protected function detectLargeTransactions(int $businessId, object $rule): array
    {
        $out = [];
        $threshold = (float) ($rule->threshold ?? 500000);
        if (! Schema::hasTable('journal_entries')) {
            return $out;
        }
        $rows = DB::table('journal_entries')
            ->where('reversed', 0)
            ->where('active', 1)
            ->where(function ($q) use ($threshold) {
                $q->where('debit', '>=', $threshold)->orWhere('credit', '>=', $threshold);
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'debit', 'credit', 'transaction_number', 'date']);
        foreach ($rows as $row) {
            $amt = max((float) $row->debit, (float) $row->credit);
            $out[] = [
                'alert_type' => 'large_transaction',
                'reference_type' => 'journal_entry',
                'reference_id' => (int) $row->id,
                'amount' => round($amt, 2),
                'explanation' => "Journal {$row->transaction_number} exceeds configured large-transaction threshold (LKR ".number_format($threshold, 2).').',
                'evidence' => ['threshold' => $threshold, 'date' => $row->date],
            ];
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    protected function detectExpenseIncrease(int $businessId, object $rule): array
    {
        $thresholdPct = (float) ($rule->threshold ?? 50);
        $from = date('Y-m-01');
        $to = date('Y-m-d');
        $prevFrom = date('Y-m-01', strtotime('-1 month'));
        $prevTo = date('Y-m-t', strtotime('-1 month'));
        $cur = \App\Services\Accounting\FinancialStatementService::profitAndLoss($from, $to);
        $prev = \App\Services\Accounting\FinancialStatementService::profitAndLoss($prevFrom, $prevTo);
        $c = (float) ($cur['expense'] ?? 0);
        $p = (float) ($prev['expense'] ?? 0);
        if ($p <= 0) {
            return [];
        }
        $pct = (($c - $p) / $p) * 100;
        if ($pct < $thresholdPct) {
            return [];
        }

        return [[
            'alert_type' => 'expense_increase',
            'reference_type' => null,
            'reference_id' => null,
            'amount' => round($c - $p, 2),
            'explanation' => sprintf('Expenses increased %.1f%% vs prior month (threshold %.1f%%). Correlation only — investigate drivers.', $pct, $thresholdPct),
            'evidence' => ['current' => $c, 'previous' => $p, 'pct' => round($pct, 2)],
        ]];
    }

    /** @return list<array<string, mixed>> */
    protected function detectRepeatedReversals(int $businessId, object $rule): array
    {
        $threshold = (int) ($rule->threshold ?? 3);
        if (! Schema::hasTable('journal_entries')) {
            return [];
        }
        $count = DB::table('journal_entries')
            ->where('reversed', 1)
            ->where('date', '>=', date('Y-m-01'))
            ->count();
        if ($count < $threshold) {
            return [];
        }

        return [[
            'alert_type' => 'repeated_reversals',
            'reference_type' => null,
            'reference_id' => null,
            'amount' => null,
            'explanation' => "{$count} reversed journal lines this month (threshold {$threshold}). Review for unusual reversal patterns.",
            'evidence' => ['count' => $count, 'threshold' => $threshold],
        ]];
    }

    /** @return list<array<string, mixed>> */
    protected function detectCashShortage(int $businessId, object $rule): array
    {
        if (! Schema::hasTable('journal_entries') || ! Schema::hasTable('chart_of_accounts')) {
            return [];
        }
        $bal = (float) DB::table('journal_entries as j')
            ->join('chart_of_accounts as c', 'c.id', '=', 'j.chart_of_account_id')
            ->where('j.reversed', 0)->where('j.active', 1)
            ->where(function ($q) {
                $q->where('c.name', 'like', '%Cash%')->orWhere('c.name', 'like', '%Bank%');
            })
            ->selectRaw('ROUND(SUM(COALESCE(j.debit,0)-COALESCE(j.credit,0)),2) as bal')
            ->value('bal');

        if ($bal >= (float) ($rule->threshold ?? 0)) {
            return [];
        }

        return [[
            'alert_type' => 'cash_shortage',
            'reference_type' => null,
            'reference_id' => null,
            'amount' => round($bal, 2),
            'explanation' => 'Cash/bank GL net is below configured threshold. Indicator for review only.',
            'evidence' => ['balance' => $bal, 'threshold' => $rule->threshold],
        ]];
    }
}
